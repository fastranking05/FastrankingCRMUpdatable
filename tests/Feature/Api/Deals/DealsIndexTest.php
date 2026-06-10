<?php

namespace Tests\Feature\Api\Deals;

use App\Models\Comment;
use App\Models\Deal;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupBusiness;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DealsIndexTest extends TestCase
{
    private User $user;
    private string $token;
    private FollowupBusiness $business;
    private FollowupAuthPerson $authPerson;
    private Deal $proposalDeal;
    private Deal $negotiationDeal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();

        $suffix = Str::replace('-', '', (string) Str::uuid());
        $shortSuffix = substr($suffix, 0, 12);

        $this->user = User::create([
            'first_name' => 'Deals',
            'middle_name' => null,
            'last_name' => 'Tester',
            'gender' => 'other',
            'dob' => '1995-01-15',
            'email' => 'deals_'.$shortSuffix.'@example.test',
            'mobile' => '+91'.$shortSuffix,
            'username' => 'deals_'.$shortSuffix,
            'password' => bcrypt('password'),
            'date_of_joining' => '2024-01-01',
            'emp_id' => 'EMP'.$shortSuffix,
            'status' => 'active',
            'user_type' => 'employee',
            'designation' => 'Sales Executive',
            'created_by' => null,
        ]);

        $module = Module::create([
            'name' => 'Deals',
            'description' => 'Deals module',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $role = Role::create([
            'name' => 'Deals Manager',
            'description' => 'Full deals access',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $role->modules()->syncWithoutDetaching([
            $module->id => [
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
            ],
        ]);

        $this->user->roles()->attach($role->id);
        $this->token = JWTAuth::fromUser($this->user);

        $this->business = FollowupBusiness::create([
            'name' => 'Tech Solutions Inc.',
            'category' => 'Technology',
            'type' => 'Enterprise',
            'created_by' => $this->user->id,
        ]);

        $this->authPerson = FollowupAuthPerson::create([
            'firstname' => 'David',
            'lastname' => 'Chen',
            'primaryemail' => 'david.chen@techsolutions.com',
            'primarymobile' => '+1 555-0101',
        ]);

        $this->proposalDeal = Deal::create([
            'followup_business_id' => $this->business->id,
            'auth_person_id' => $this->authPerson->id,
            'name' => 'Tech Solutions Inc.',
            'selected_service' => 'Custom AI Framework',
            'type' => 'Referral',
            'deal_stage' => 'Proposal Sent',
            'amount_exc_vat' => 100000,
            'vat' => 25000,
            'estimated_closed_date' => '2026-06-15',
            'created_by' => $this->user->id,
        ]);

        $this->negotiationDeal = Deal::create([
            'followup_business_id' => $this->business->id,
            'name' => 'Another Deal',
            'deal_stage' => 'Negotation',
            'amount_exc_vat' => 50000,
            'vat' => 10000,
            'created_by' => $this->user->id,
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'comments',
            'deals',
            'followup_auth_persons',
            'followup_businesses',
            'module_role',
            'role_user',
            'modules',
            'roles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('gender');
            $table->date('dob');
            $table->string('email')->nullable()->unique();
            $table->string('mobile')->nullable()->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->date('date_of_joining');
            $table->string('emp_id')->unique();
            $table->string('status');
            $table->string('user_type');
            $table->string('designation');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('module_role', function (Blueprint $table) {
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('role_id');
            $table->boolean('can_create')->default(false);
            $table->boolean('can_read')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();
        });

        Schema::create('followup_businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->string('website')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('followup_auth_persons', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('firstname')->nullable();
            $table->string('middlename')->nullable();
            $table->string('lastname')->nullable();
            $table->string('job_title')->nullable();
            $table->string('primaryemail')->nullable();
            $table->string('primarymobile')->nullable();
            $table->string('primaryphone')->nullable();
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->string('id', 15)->primary();
            $table->unsignedBigInteger('followup_business_id');
            $table->unsignedBigInteger('auth_person_id')->nullable();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('deal_stage')->nullable();
            $table->text('lost_reason')->nullable();
            $table->decimal('probability', 5, 2)->nullable();
            $table->date('estimated_closed_date')->nullable();
            $table->string('selected_service')->nullable();
            $table->decimal('amount_exc_vat', 15, 2)->nullable();
            $table->decimal('vat', 15, 2)->nullable();
            $table->string('next_activity')->nullable();
            $table->string('priority')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('followup_business_id');
            $table->text('comment')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function test_index_filters_by_deal_stage_and_returns_summary(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/deals?deal_stage=Proposal Sent');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.deal_count', 1)
            ->assertJsonPath('data.summary.total_value', 125000);

        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($this->proposalDeal->id, $ids);
        $this->assertNotContains($this->negotiationDeal->id, $ids);
    }

    public function test_index_rejects_invalid_deal_stage(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/deals?deal_stage=Invalid Stage');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_store_creates_deal_and_business_comments(): void
    {
        $payload = [
            'followup_business_id' => $this->business->id,
            'auth_person_id' => $this->authPerson->id,
            'name' => 'New Enterprise Deal',
            'type' => 'Referral',
            'selected_service' => 'SEO Package',
            'amount_exc_vat' => 80000,
            'vat' => 16000,
            'comments' => [
                [
                    'comment' => 'Initial deal created from consultation follow-up.',
                    'new_status' => 'New Deal Created',
                ],
            ],
        ];

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/deals', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.deal_stage', 'New Deal Created')
            ->assertJsonPath('data.value', 96000);

        $dealId = $response->json('data.id');
        $this->assertNotEmpty($dealId);
        $this->assertDatabaseHas('deals', ['id' => $dealId, 'name' => 'New Enterprise Deal']);
        $this->assertDatabaseHas('comments', [
            'followup_business_id' => $this->business->id,
            'comment' => 'Initial deal created from consultation follow-up.',
            'new_status' => 'New Deal Created',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_show_returns_deal_with_comments(): void
    {
        Comment::create([
            'followup_business_id' => $this->business->id,
            'comment' => 'Sent proposal to client.',
            'new_status' => 'Proposal Sent',
            'created_by' => $this->user->id,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/deals/'.$this->proposalDeal->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $this->proposalDeal->id)
            ->assertJsonPath('data.company.name', 'Tech Solutions Inc.')
            ->assertJsonPath('data.contact.name', 'David Chen')
            ->assertJsonPath('data.value', 125000);

        $this->assertNotEmpty($response->json('data.comments'));
    }

    public function test_update_changes_deal_and_appends_comments(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/deals/'.$this->proposalDeal->id, [
                'deal_stage' => 'Negotation',
                'comments' => [
                    [
                        'comment' => 'Client requested revised pricing.',
                        'old_status' => 'Proposal Sent',
                        'new_status' => 'Negotation',
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.deal_stage', 'Negotation');

        $this->assertDatabaseHas('deals', [
            'id' => $this->proposalDeal->id,
            'deal_stage' => 'Negotation',
        ]);

        $this->assertDatabaseHas('comments', [
            'followup_business_id' => $this->business->id,
            'comment' => 'Client requested revised pricing.',
            'old_status' => 'Proposal Sent',
            'new_status' => 'Negotation',
        ]);
    }

    public function test_destroy_deletes_deal(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/deals/'.$this->negotiationDeal->id);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('deals', ['id' => $this->negotiationDeal->id]);
    }
}
