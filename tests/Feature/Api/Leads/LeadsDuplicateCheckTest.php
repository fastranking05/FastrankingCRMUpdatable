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

        // Create test user
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        // Create test business
        $this->business = FollowupBusiness::create([
            'name' => 'Test Corporation',
            'category' => 'Technology',
            'type' => 'Standard',
            'phone' => '+1234567890',
            'email' => 'contact@testcorp.com',
            'created_by' => $this->user->id,
        ]);

        // Create test auth person
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

        // Associate auth person with business
        $this->business->authPersons()->attach($this->authPerson->id);
    }

    /**
     * Test duplicate check with no duplicates
     */
    public function test_duplicate_check_no_duplicates(): void
    {
        $payload = [
            'business_phone' => '+9999999999',
            'business_email' => 'new@example.com',
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

    /**
     * Test duplicate check with business phone duplicate
     */
    public function test_duplicate_check_business_phone_duplicate(): void
    {
        $payload = [
            'business_phone' => '+1234567890',
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

        $this->assertArrayHasKey('business_phone', $response->json('data.duplicates'));
        $this->assertEquals(true, $response->json('data.duplicates.business_phone.exists'));
        $this->assertEquals($this->business->id, $response->json('data.duplicates.business_phone.lead_id'));
        $this->assertEquals('Test Corporation', $response->json('data.duplicates.business_phone.business_name'));
    }

    /**
     * Test duplicate check with business email duplicate
     */
    public function test_duplicate_check_business_email_duplicate(): void
    {
        $payload = [
            'business_email' => 'contact@testcorp.com',
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

        $this->assertArrayHasKey('business_email', $response->json('data.duplicates'));
        $this->assertEquals(true, $response->json('data.duplicates.business_email.exists'));
        $this->assertEquals($this->business->id, $response->json('data.duplicates.business_email.lead_id'));
    }

    /**
     * Test duplicate check with auth person phone duplicate (primaryphone)
     */
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

    /**
     * Test duplicate check with auth person phone duplicate (altphone)
     */
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

    /**
     * Test duplicate check with auth person mobile duplicate (primarymobile)
     */
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

    /**
     * Test duplicate check with auth person mobile duplicate (altmobile)
     */
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

    /**
     * Test duplicate check with auth person email duplicate (primaryemail)
     */
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

    /**
     * Test duplicate check with auth person email duplicate (altemail)
     */
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

    /**
     * Test duplicate check with multiple duplicates
     */
    public function test_duplicate_check_multiple_duplicates(): void
    {
        $payload = [
            'business_phone' => '+1234567890',
            'business_email' => 'contact@testcorp.com',
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
        $this->assertCount(5, $duplicates);
        $this->assertArrayHasKey('business_phone', $duplicates);
        $this->assertArrayHasKey('business_email', $duplicates);
        $this->assertArrayHasKey('auth_person_phone', $duplicates);
        $this->assertArrayHasKey('auth_person_mobile', $duplicates);
        $this->assertArrayHasKey('auth_person_email', $duplicates);
    }

    /**
     * Test duplicate check with empty request
     */
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

    /**
     * Test duplicate check with invalid email format
     */
    public function test_duplicate_check_invalid_email(): void
    {
        $payload = [
            'business_email' => 'invalid-email',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['business_email']);
    }

    /**
     * Test duplicate check requires authentication
     */
    public function test_duplicate_check_requires_authentication(): void
    {
        Sanctum::actingAs(null);

        $payload = [
            'business_phone' => '+1234567890',
        ];

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', $payload);

        $response->assertStatus(401);
    }
}
