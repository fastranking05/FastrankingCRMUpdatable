<?php

namespace Tests\Feature\Api\Leads;

use App\Models\User;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadsDuplicateCheckTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected FollowupBusiness $business;
    protected FollowupAuthPerson $authPerson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->business = FollowupBusiness::create([
            'name' => 'Test Corporation',
            'category' => 'Technology',
            'type' => 'Standard',
            'created_by' => $this->user->id,
        ]);

        $this->authPerson = FollowupAuthPerson::create([
            'title' => 'Mr.',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'primaryphone' => '+1234567891',
            'altphone' => '+1234567892',
            'primarymobile' => '+1234567893',
            'altmobile' => '+1234567894',
            'primaryemail' => 'john.doe@testcorp.com',
            'altemail' => 'john.alternate@testcorp.com',
            'created_by' => $this->user->id,
        ]);

        $this->business->authPersons()->attach($this->authPerson->id);
    }

    public function test_duplicate_check_no_duplicates(): void
    {
        $payload = [
            'auth_person_phone' => '+9999999998',
            'auth_person_mobile' => '+9999999997',
            'auth_person_email' => 'newperson@example.com',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'No duplicates found',
                'data' => [
                    'has_duplicates' => false,
                    'duplicates' => []
                ]
            ]);
    }

    public function test_duplicate_check_auth_person_phone_primary_duplicate(): void
    {
        $payload = [
            'auth_person_phone' => '+1234567891',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Duplicates found',
                'data' => [
                    'has_duplicates' => true,
                ]
            ]);

        $this->assertArrayHasKey('auth_person_phone', $response->json('data.duplicates'));
        $this->assertEquals(true, $response->json('data.duplicates.auth_person_phone.exists'));
        $this->assertEquals($this->business->id, $response->json('data.duplicates.auth_person_phone.lead_id'));
        $this->assertEquals('John Doe', $response->json('data.duplicates.auth_person_phone.auth_person_name'));
    }

    public function test_duplicate_check_auth_person_phone_alt_duplicate(): void
    {
        $payload = [
            'auth_person_phone' => '+1234567892',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Duplicates found',
                'data' => [
                    'has_duplicates' => true,
                ]
            ]);

        $this->assertArrayHasKey('auth_person_phone', $response->json('data.duplicates'));
        $this->assertEquals(true, $response->json('data.duplicates.auth_person_phone.exists'));
    }

    public function test_duplicate_check_auth_person_mobile_primary_duplicate(): void
    {
        $payload = [
            'auth_person_mobile' => '+1234567893',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Duplicates found',
                'data' => [
                    'has_duplicates' => true,
                ]
            ]);

        $this->assertArrayHasKey('auth_person_mobile', $response->json('data.duplicates'));
        $this->assertEquals(true, $response->json('data.duplicates.auth_person_mobile.exists'));
    }

    public function test_duplicate_check_auth_person_mobile_alt_duplicate(): void
    {
        $payload = [
            'auth_person_mobile' => '+1234567894',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Duplicates found',
                'data' => [
                    'has_duplicates' => true,
                ]
            ]);

        $this->assertArrayHasKey('auth_person_mobile', $response->json('data.duplicates'));
        $this->assertEquals(true, $response->json('data.duplicates.auth_person_mobile.exists'));
    }

    public function test_duplicate_check_auth_person_email_primary_duplicate(): void
    {
        $payload = [
            'auth_person_email' => 'john.doe@testcorp.com',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Duplicates found',
                'data' => [
                    'has_duplicates' => true,
                ]
            ]);

        $this->assertArrayHasKey('auth_person_email', $response->json('data.duplicates'));
        $this->assertEquals(true, $response->json('data.duplicates.auth_person_email.exists'));
        $this->assertEquals($this->business->id, $response->json('data.duplicates.auth_person_email.lead_id'));
        $this->assertEquals('John Doe', $response->json('data.duplicates.auth_person_email.auth_person_name'));
    }

    public function test_duplicate_check_auth_person_email_alt_duplicate(): void
    {
        $payload = [
            'auth_person_email' => 'john.alternate@testcorp.com',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Duplicates found',
                'data' => [
                    'has_duplicates' => true,
                ]
            ]);

        $this->assertArrayHasKey('auth_person_email', $response->json('data.duplicates'));
        $this->assertEquals(true, $response->json('data.duplicates.auth_person_email.exists'));
    }

    public function test_duplicate_check_multiple_duplicates(): void
    {
        $payload = [
            'auth_person_phone' => '+1234567891',
            'auth_person_mobile' => '+1234567893',
            'auth_person_email' => 'john.doe@testcorp.com',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Duplicates found',
                'data' => [
                    'has_duplicates' => true,
                ]
            ]);

        $duplicates = $response->json('data.duplicates');
        $this->assertCount(3, $duplicates);
        $this->assertArrayHasKey('auth_person_phone', $duplicates);
        $this->assertArrayHasKey('auth_person_mobile', $duplicates);
        $this->assertArrayHasKey('auth_person_email', $duplicates);
    }

    public function test_duplicate_check_empty_request(): void
    {
        $payload = [];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'No duplicates found',
                'data' => [
                    'has_duplicates' => false,
                    'duplicates' => []
                ]
            ]);
    }

    public function test_duplicate_check_invalid_email(): void
    {
        $payload = [
            'auth_person_email' => 'invalid-email',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['auth_person_email']);
    }

    public function test_duplicate_check_requires_authentication(): void
    {
        Sanctum::actingAs(null);

        $payload = [
            'auth_person_phone' => '+1234567890',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(401);
    }
}
