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
            'trading_name' => 'Test Trading',
            'website' => 'https://testcorp.com',
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
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'business_name' => 'Unique Business Ltd',
            'website' => 'https://unique-example.com',
            'phone' => '+9999999998',
            'mobile' => '+9999999997',
            'email' => 'newperson@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'No duplicates found',
                'data' => [
                    'has_duplicates' => false,
                    'duplicates' => [],
                ],
            ]);
    }

    public function test_duplicate_check_business_name_duplicate(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'business_name' => 'test corporation',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.has_duplicates', true)
            ->assertJsonPath('data.duplicates.business_name.exists', true)
            ->assertJsonPath('data.duplicates.business_name.lead_id', $this->business->id)
            ->assertJsonPath('data.duplicates.business_name.business_name', 'Test Corporation');
    }

    public function test_duplicate_check_trading_name_duplicate(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'business_name' => 'Test Trading',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.duplicates.business_name.exists', true)
            ->assertJsonPath('data.duplicates.business_name.lead_id', $this->business->id);
    }

    public function test_duplicate_check_website_duplicate(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'website' => 'https://testcorp.com/',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.duplicates.website.exists', true)
            ->assertJsonPath('data.duplicates.website.lead_id', $this->business->id)
            ->assertJsonPath('data.duplicates.website.website', 'https://testcorp.com');
    }

    public function test_duplicate_check_phone_primary_duplicate(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'phone' => '+1234567891',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.duplicates.phone.exists', true)
            ->assertJsonPath('data.duplicates.phone.lead_id', $this->business->id)
            ->assertJsonPath('data.duplicates.phone.auth_person_name', 'John Doe');
    }

    public function test_duplicate_check_phone_alt_duplicate(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'phone' => '+1234567892',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.duplicates.phone.exists', true);
    }

    public function test_duplicate_check_mobile_primary_duplicate(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'mobile' => '+1234567893',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.duplicates.mobile.exists', true);
    }

    public function test_duplicate_check_mobile_alt_duplicate(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'mobile' => '+1234567894',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.duplicates.mobile.exists', true);
    }

    public function test_duplicate_check_email_primary_duplicate(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'email' => 'john.doe@testcorp.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.duplicates.email.exists', true)
            ->assertJsonPath('data.duplicates.email.lead_id', $this->business->id)
            ->assertJsonPath('data.duplicates.email.auth_person_name', 'John Doe');
    }

    public function test_duplicate_check_email_alt_duplicate(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'email' => 'john.alternate@testcorp.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.duplicates.email.exists', true);
    }

    public function test_duplicate_check_supports_legacy_auth_person_field_names(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'auth_person_phone' => '+1234567891',
            'auth_person_mobile' => '+1234567893',
            'auth_person_email' => 'john.doe@testcorp.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.has_duplicates', true);

        $duplicates = $response->json('data.duplicates');
        $this->assertArrayHasKey('phone', $duplicates);
        $this->assertArrayHasKey('mobile', $duplicates);
        $this->assertArrayHasKey('email', $duplicates);
    }

    public function test_duplicate_check_multiple_duplicates(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'business_name' => 'Test Corporation',
            'website' => 'https://testcorp.com',
            'phone' => '+1234567891',
            'mobile' => '+1234567893',
            'email' => 'john.doe@testcorp.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.has_duplicates', true);

        $duplicates = $response->json('data.duplicates');
        $this->assertCount(5, $duplicates);
        $this->assertArrayHasKey('business_name', $duplicates);
        $this->assertArrayHasKey('website', $duplicates);
        $this->assertArrayHasKey('phone', $duplicates);
        $this->assertArrayHasKey('mobile', $duplicates);
        $this->assertArrayHasKey('email', $duplicates);
    }

    public function test_duplicate_check_empty_request_returns_validation_error(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', []);

        $response->assertStatus(422);
    }

    public function test_duplicate_check_invalid_email(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_duplicate_check_invalid_website(): void
    {
        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'website' => 'not-a-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website']);
    }

    public function test_duplicate_check_requires_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->postJson('/api/admin/leads/leads/check-duplicate', [
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(401);
    }
}
