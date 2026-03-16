<?php

namespace Tests\Feature\Api\Followup;

use App\Models\User;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupDetail;
use App\Models\Comment;
use App\Models\TimeSlot;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowupUpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected FollowupBusiness $business;
    protected FollowupAuthPerson $authPerson1;
    protected FollowupAuthPerson $authPerson2;
    protected FollowupAuthPerson $authPerson3;
    protected TimeSlot $timeSlot;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        // Create test business
        $this->business = FollowupBusiness::factory()->create([
            'name' => 'Test Corporation',
            'category' => 'Technology',
            'type' => 'Standard',
            'created_by' => $this->user->id,
        ]);

        // Create test auth persons
        $this->authPerson1 = FollowupAuthPerson::factory()->create([
            'title' => 'Mr.',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'primarymobile' => '+1234567891',
            'primaryemail' => 'john.doe@test.com',
            'created_by' => $this->user->id,
        ]);

        $this->authPerson2 = FollowupAuthPerson::factory()->create([
            'title' => 'Ms.',
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'primarymobile' => '+1234567892',
            'primaryemail' => 'jane.smith@test.com',
            'created_by' => $this->user->id,
        ]);

        $this->authPerson3 = FollowupAuthPerson::factory()->create([
            'title' => 'Dr.',
            'firstname' => 'Robert',
            'lastname' => 'Johnson',
            'primarymobile' => '+1234567893',
            'primaryemail' => 'robert.johnson@test.com',
            'created_by' => $this->user->id,
        ]);

        // Associate auth persons with business
        $this->business->authPersons()->attach([
            $this->authPerson1->id,
            $this->authPerson2->id,
            $this->authPerson3->id,
        ]);

        // Create test time slot
        $this->timeSlot = TimeSlot::factory()->create([
            'name' => 'Test Slot',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
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
                'website' => 'https://updated.com',
                'phone' => '+9876543210',
                'email' => 'updated@corp.com',
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
                    'id' => $this->authPerson2->id,
                    'title' => 'Ms.',
                    'firstname' => 'Jane Updated',
                    'lastname' => 'Smith',
                    'primarymobile' => '+1234567892',
                    'primaryemail' => 'jane.smith.updated@test.com',
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

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'authPersons' => [
                        '*' => [
                            'id',
                            'title',
                            'firstname',
                            'lastname',
                            'primarymobile',
                            'primaryemail',
                        ],
                    ],
                    'followupDetails' => [
                        '*' => [
                            'id',
                            'source',
                            'status',
                            'date',
                            'time',
                        ],
                    ],
                    'comments' => [
                        '*' => [
                            'id',
                            'comment',
                            'old_status',
                            'new_status',
                        ],
                    ],
                ],
            ]);

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

        // Verify follow-up detail was created
        $this->assertDatabaseHas('followup_details', [
            'followup_business_id' => $this->business->id,
            'source' => 'Test Source',
            'status' => 'In Progress',
        ]);

        // Verify comment was created
        $this->assertDatabaseHas('comments', [
            'followup_business_id' => $this->business->id,
            'comment' => 'Test comment for update',
            'old_status' => 'Followup',
            'new_status' => 'In Progress',
        ]);
    }

    /**
     * Test follow-up update with appointment creation
     */
    public function test_followup_update_with_appointment(): void
    {
        $payload = [
            'business' => [
                'name' => 'Appointment Corporation',
                'category' => 'Technology Services',
                'type' => 'Enterprise',
                'website' => 'https://appointment.com',
                'phone' => '+9876543210',
                'email' => 'appointment@corp.com',
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
                'time_slot_id' => $this->timeSlot->id,
                'current_status' => 'Appointment Booked',
                'status' => 'Appointment Booked',
            ],
        ];

        $response = $this->putJson("/api/followup/{$this->business->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'authPersons',
                    'appointment' => [
                        'id',
                        'followup_business_id',
                        'date',
                        'time_slot_id',
                        'current_status',
                        'status',
                    ],
                ],
            ]);

        // Verify business was updated
        $this->assertDatabaseHas('followup_businesses', [
            'id' => $this->business->id,
            'name' => 'Appointment Corporation',
        ]);

        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'followup_business_id' => $this->business->id,
            'date' => '2024-03-21',
            'time_slot_id' => $this->timeSlot->id,
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

        // Verify no follow-up details were created (skipped when appointment is present)
        $this->assertDatabaseMissing('followup_details', [
            'followup_business_id' => $this->business->id,
            'source' => 'Test Source',
        ]);
    }

    /**
     * Test auth person sync behavior (delete missing auth persons)
     */
    public function test_auth_person_sync_behavior(): void
    {
        // Initial state: 3 auth persons
        $this->assertEquals(3, $this->business->authPersons()->count());

        $payload = [
            'business' => [
                'name' => 'Sync Test Corporation',
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
                // authPerson2 and authPerson3 are removed - should be deleted
                [
                    'title' => 'Ms.',
                    'firstname' => 'Sarah',
                    'lastname' => 'Williams',
                    'primarymobile' => '+1234567894',
                    'primaryemail' => 'sarah.williams@test.com',
                ],
            ],
        ];

        $response = $this->putJson("/api/followup/{$this->business->id}", $payload);

        $response->assertStatus(200);

        // Verify only 2 auth persons remain
        $this->business->refresh();
        $this->assertEquals(2, $this->business->authPersons()->count());

        // Verify John still exists
        $this->assertDatabaseHas('followup_auth_persons', [
            'id' => $this->authPerson1->id,
        ]);

        // Verify Sarah was created
        $this->assertDatabaseHas('followup_auth_persons', [
            'firstname' => 'Sarah',
            'lastname' => 'Williams',
        ]);

        // Verify auth persons are properly associated
        $this->assertTrue($this->business->authPersons()->contains($this->authPerson1->id));
        $this->assertFalse($this->business->authPersons()->contains($this->authPerson2->id));
        $this->assertFalse($this->business->authPersons()->contains($this->authPerson3->id));
    }

    /**
     * Test validation errors for duplicate emails/mobile numbers
     */
    public function test_validation_errors_for_duplicates(): void
    {
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
                    'primarymobile' => '+1234567892', // Duplicate - same as authPerson2
                    'primaryemail' => 'john.doe@test.com',
                ],
            ],
        ];

        $response = $this->putJson("/api/followup/{$this->business->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['auth_persons.0.primarymobile']);
    }

    /**
     * Test appointment ID generation format
     */
    public function test_appointment_id_generation(): void
    {
        $payload = [
            'business' => [
                'name' => 'ID Test Corporation',
            ],
            'appointment' => [
                'date' => '2024-03-21',
                'time_slot_id' => $this->timeSlot->id,
                'current_status' => 'Appointment Booked',
                'status' => 'Appointment Booked',
            ],
        ];

        $response = $this->putJson("/api/followup/{$this->business->id}", $payload);

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
