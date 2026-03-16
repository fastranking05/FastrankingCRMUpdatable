<?php

namespace Tests\Feature\Api\Followup;

use App\Models\User;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowupUpdateSimpleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected FollowupBusiness $business;
    protected FollowupAuthPerson $authPerson1;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        // Create test business with minimal data
        $this->business = FollowupBusiness::create([
            'name' => 'Test Corporation',
            'category' => 'Technology',
            'type' => 'Standard',
            'created_by' => $this->user->id,
        ]);

        // Create test auth person with minimal data
        $this->authPerson1 = FollowupAuthPerson::create([
            'title' => 'Mr.',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'primarymobile' => '+1234567891',
            'primaryemail' => 'john.doe@test.com',
            'created_by' => $this->user->id,
        ]);

        // Associate auth person with business
        $this->business->authPersons()->attach($this->authPerson1->id);
    }

    /**
     * Test basic follow-up update without appointment
     */
    public function test_basic_followup_update(): void
    {
        $payload = [
            'business' => [
                'name' => 'Updated Corporation',
                'category' => 'Technology Services',
                'type' => 'Premium',
            ],
            'auth_persons' => [
                [
                    'id' => $this->authPerson1->id,
                    'title' => 'Mr.',
                    'firstname' => 'John Updated',
                    'lastname' => 'Doe',
                    'primarymobile' => '+1234567891',
                    'primaryemail' => 'john.doe.updated@test.com',
                ],
                [
                    'title' => 'Ms.',
                    'firstname' => 'Sarah',
                    'lastname' => 'Williams',
                    'primarymobile' => '+1234567894',
                    'primaryemail' => 'sarah.williams@test.com',
                ],
            ],
            'followup_details' => [
                [
                    'source' => 'Test Source',
                    'status' => 'In Progress',
                    'date' => '2024-03-17',
                    'time' => '15:30',
                ],
            ],
            'comments' => [
                [
                    'comment' => 'Test comment for update',
                    'old_status' => 'Followup',
                    'new_status' => 'In Progress',
                ],
            ],
        ];

        $response = $this->putJson("/api/followup/{$this->business->id}", $payload);

        if ($response->status() !== 200) {
            dump('Response Status:', $response->status());
            dump('Response Body:', $response->json());
        }

        $response->assertStatus(200);

        // Verify business was updated
        $this->assertDatabaseHas('followup_businesses', [
            'id' => $this->business->id,
            'name' => 'Updated Corporation',
            'category' => 'Technology Services',
            'type' => 'Premium',
        ]);

        // Verify auth persons were updated/created
        $this->assertDatabaseHas('followup_auth_persons', [
            'id' => $this->authPerson1->id,
            'firstname' => 'John Updated',
            'primaryemail' => 'john.doe.updated@test.com',
        ]);

        $this->assertDatabaseHas('followup_auth_persons', [
            'firstname' => 'Sarah',
            'lastname' => 'Williams',
            'primarymobile' => '+1234567894',
            'primaryemail' => 'sarah.williams@test.com',
        ]);
    }

    /**
     * Test follow-up update with appointment creation
     */
    public function test_followup_update_with_appointment(): void
    {
        // Create a simple time slot for testing
        $timeSlot = \App\Models\TimeSlot::create([
            'name' => 'Test Slot',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'duration_minutes' => 60,
            'is_active' => true,
            'max_concurrent_bookings' => 1,
        ]);

        $payload = [
            'business' => [
                'name' => 'Appointment Corporation',
                'category' => 'Technology Services',
                'type' => 'Enterprise',
            ],
            'auth_persons' => [
                [
                    'id' => $this->authPerson1->id,
                    'title' => 'Mr.',
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'primarymobile' => '+1234567891',
                    'primaryemail' => 'john.doe@test.com',
                ],
                [
                    'title' => 'Ms.',
                    'firstname' => 'Sarah',
                    'lastname' => 'Williams',
                    'primarymobile' => '+1234567894',
                    'primaryemail' => 'sarah.williams@test.com',
                ],
            ],
            'comments' => [
                [
                    'comment' => 'Appointment booked successfully',
                    'old_status' => 'Followup',
                    'new_status' => 'Appointment Booked',
                ],
            ],
            'appointment' => [
                'date' => '2024-03-21',
                'time_slot_id' => $timeSlot->id,
                'current_status' => 'Appointment Booked',
                'status' => 'Appointment Booked',
            ],
        ];

        $response = $this->putJson("/api/followup/{$this->business->id}", $payload);

        if ($response->status() !== 200) {
            dump('Response Status:', $response->status());
            dump('Response Body:', $response->json());
        }

        $response->assertStatus(200);

        // Verify business was updated
        $this->assertDatabaseHas('followup_businesses', [
            'id' => $this->business->id,
            'name' => 'Appointment Corporation',
        ]);

        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'followup_business_id' => $this->business->id,
            'date' => '2024-03-21',
            'time_slot_id' => $timeSlot->id,
            'status' => 'Appointment Booked',
            'current_status' => 'Appointment Booked',
        ]);

        // Verify comment was created
        $this->assertDatabaseHas('comments', [
            'followup_business_id' => $this->business->id,
            'comment' => 'Appointment booked successfully',
            'old_status' => 'Followup',
            'new_status' => 'Appointment Booked',
        ]);
    }

    /**
     * Test validation errors for duplicate emails/mobile numbers
     */
    public function test_validation_errors_for_duplicates(): void
    {
        // Create another auth person with conflicting data
        $conflictingPerson = FollowupAuthPerson::create([
            'title' => 'Ms.',
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'primarymobile' => '+1234567892',
            'primaryemail' => 'jane.smith@test.com',
            'created_by' => $this->user->id,
        ]);

        $payload = [
            'business' => [
                'name' => 'Test Corporation',
            ],
            'auth_persons' => [
                [
                    'id' => $this->authPerson1->id,
                    'title' => 'Mr.',
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'primarymobile' => '+1234567892', // Duplicate - same as conflictingPerson
                    'primaryemail' => 'john.doe@test.com',
                ],
            ],
        ];

        $response = $this->putJson("/api/followup/{$this->business->id}", $payload);

        // Should return validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['auth_persons.0.primarymobile']);
    }

    /**
     * Test appointment ID generation format
     */
    public function test_appointment_id_generation(): void
    {
        // Create a simple time slot for testing
        $timeSlot = \App\Models\TimeSlot::create([
            'name' => 'Test Slot',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'duration_minutes' => 60,
            'is_active' => true,
            'max_concurrent_bookings' => 1,
        ]);

        $payload = [
            'business' => [
                'name' => 'ID Test Corporation',
            ],
            'appointment' => [
                'date' => '2024-03-21',
                'time_slot_id' => $timeSlot->id,
                'current_status' => 'Appointment Booked',
                'status' => 'Appointment Booked',
            ],
        ];

        $response = $this->putJson("/api/followup/{$this->business->id}", $payload);

        if ($response->status() !== 200) {
            dump('Response Status:', $response->status());
            dump('Response Body:', $response->json());
        }

        $response->assertStatus(200);

        $appointmentId = $response->json('data.appointment.id');
        
        // Verify ID format: FRMID followed by 8 digits
        $this->assertMatchesRegularExpression('/^FRMID\d{8}$/', $appointmentId);
        
        // Verify appointment exists in database
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'followup_business_id' => $this->business->id,
        ]);
    }
}
