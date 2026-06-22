<?php

namespace Tests\Feature\Api\Leads;

use App\Models\Department;
use App\Models\FollowupBusiness;
use App\Models\FollowupDetail;
use App\Models\Module;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\SetsUpUserWithModuleReadPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class LeadsFilterTest extends TestCase
{
    use SetsUpUserWithModuleReadPermissions;

    private User $otherUser;
    private string $token;
    private FollowupBusiness $techLead;
    private FollowupBusiness $healthLead;
    private FollowupBusiness $otherUserLead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        $this->seedUsersAndLeads();
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'comments',
            'followup_details',
            'followup_business_auth_person',
            'followup_auth_persons',
            'followup_businesses',
            'department_user',
            'team_user',
            'departments',
            'teams',
            'module_department',
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

        Schema::create('module_department', function (Blueprint $table) {
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('department_id');
            $table->boolean('can_create')->default(false);
            $table->boolean('can_read')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('team_user', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('department_user', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('followup_businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->string('source_name')->nullable();
            $table->string('sub_source')->nullable();
            $table->string('website')->nullable();
            $table->date('latest_followup_date')->nullable();
            $table->time('latest_followup_time')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('followup_auth_persons', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('primaryemail')->nullable();
            $table->string('primarymobile')->nullable();
            $table->timestamps();
        });

        Schema::create('followup_business_auth_person', function (Blueprint $table) {
            $table->unsignedBigInteger('followup_business_id');
            $table->unsignedBigInteger('followup_auth_person_id');
            $table->timestamps();
        });

        Schema::create('followup_details', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->unsignedBigInteger('followup_business_id');
            $table->string('status')->nullable();
            $table->date('date')->nullable();
            $table->time('time')->nullable();
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

    private function seedUsersAndLeads(): void
    {
        $suffix = Str::replace('-', '', (string) Str::uuid());
        $shortSuffix = substr($suffix, 0, 12);

        $this->authenticatedUser = User::create([
            'first_name' => 'Leads',
            'middle_name' => null,
            'last_name' => 'Admin',
            'gender' => 'other',
            'dob' => '1995-01-15',
            'email' => 'leads_admin_'.$shortSuffix.'@example.test',
            'mobile' => '+91'.$shortSuffix,
            'username' => 'leads_admin_'.$shortSuffix,
            'password' => bcrypt('password'),
            'date_of_joining' => '2024-01-01',
            'emp_id' => 'EMP'.$shortSuffix,
            'status' => 'active',
            'user_type' => 'admin',
            'designation' => 'Admin',
            'created_by' => null,
        ]);

        $this->otherUser = User::create([
            'first_name' => 'Other',
            'middle_name' => null,
            'last_name' => 'Executive',
            'gender' => 'other',
            'dob' => '1995-01-15',
            'email' => 'leads_other_'.$shortSuffix.'@example.test',
            'mobile' => '+92'.$shortSuffix,
            'username' => 'leads_other_'.$shortSuffix,
            'password' => bcrypt('password'),
            'date_of_joining' => '2024-01-01',
            'emp_id' => 'EMP2'.$shortSuffix,
            'status' => 'active',
            'user_type' => 'employee',
            'designation' => 'Executive',
            'created_by' => null,
        ]);

        $module = Module::create([
            'name' => 'Leads',
            'description' => 'Leads module',
            'status' => 'active',
            'created_by' => $this->authenticatedUser->id,
        ]);

        $department = Department::create([
            'name' => 'Leads Reader '.$shortSuffix,
            'description' => 'Read leads',
            'status' => 'active',
            'created_by' => $this->authenticatedUser->id,
        ]);

        $department->modules()->syncWithoutDetaching([
            $module->id => [
                'can_create' => false,
                'can_read' => true,
                'can_update' => false,
                'can_delete' => false,
            ],
        ]);

        $this->authenticatedUser->departments()->attach($department->id);
        $this->token = JWTAuth::fromUser($this->authenticatedUser);

        $this->techLead = FollowupBusiness::create([
            'name' => 'ABC Tech Solutions',
            'category' => 'Technology Services',
            'type' => 'SME',
            'source_name' => 'Website',
            'created_by' => $this->authenticatedUser->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->healthLead = FollowupBusiness::create([
            'name' => 'City Health Clinic',
            'category' => 'Healthcare',
            'type' => 'Enterprise Client',
            'source_name' => 'Referral',
            'created_by' => $this->authenticatedUser->id,
        ]);
        $this->healthLead->forceFill([
            'created_at' => Carbon::now()->subMonth(),
            'updated_at' => Carbon::now()->subMonth(),
        ])->saveQuietly();

        $this->otherUserLead = FollowupBusiness::create([
            'name' => 'Other User Corp',
            'category' => 'Finance',
            'type' => 'Startup',
            'source_name' => 'Cold Call',
            'created_by' => $this->otherUser->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        FollowupDetail::create([
            'followup_business_id' => $this->techLead->id,
            'status' => 'New',
            'date' => Carbon::today()->toDateString(),
            'time' => '10:00:00',
            'created_by' => $this->authenticatedUser->id,
        ]);
    }

    public function test_filter_options_returns_expected_structure(): void
    {
        $response = $this->getJson('/api/leads/filter-options', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'date_filters',
                    'date_columns',
                    'scope_options',
                    'category_options',
                    'type_options',
                    'source_name_options',
                    'status_options',
                ],
            ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('today', $data['date_filters']);
        $this->assertArrayHasKey('created_at', $data['date_columns']);
        $this->assertContains('Website', $data['source_name_options']);
        $this->assertContains('Referral', $data['source_name_options']);
    }

    public function test_leads_filter_returns_cursor_paginated_results(): void
    {
        $response = $this->postJson('/api/leads/leads-filter', [
            'scope' => 'all',
            'per_page' => 15,
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Leads retrieved successfully');

        $this->assertCursorPaginatorShape($response->json('data'));
        $this->assertGreaterThanOrEqual(3, count($response->json('data.data')));
    }

    public function test_leads_filter_by_category(): void
    {
        $response = $this->postJson('/api/leads/leads-filter', [
            'scope' => 'all',
            'category' => 'Healthcare',
            'per_page' => 15,
        ], $this->authHeaders());

        $response->assertOk();
        $names = collect($response->json('data.data'))->pluck('name')->all();

        $this->assertContains('City Health Clinic', $names);
        $this->assertNotContains('ABC Tech Solutions', $names);
    }

    public function test_leads_filter_by_source_name(): void
    {
        $response = $this->postJson('/api/leads/leads-filter', [
            'scope' => 'all',
            'source_name' => 'Website',
            'per_page' => 15,
        ], $this->authHeaders());

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($this->techLead->id, $ids);
        $this->assertNotContains($this->healthLead->id, $ids);
    }

    public function test_leads_filter_by_search(): void
    {
        $response = $this->postJson('/api/leads/leads-filter', [
            'scope' => 'all',
            'search' => 'ABC Tech',
            'per_page' => 15,
        ], $this->authHeaders());

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($this->techLead->id, $ids);
        $this->assertNotContains($this->healthLead->id, $ids);
    }

    public function test_leads_filter_by_status(): void
    {
        $response = $this->postJson('/api/leads/leads-filter', [
            'scope' => 'all',
            'status' => 'New',
            'per_page' => 15,
        ], $this->authHeaders());

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($this->techLead->id, $ids);
        $this->assertNotContains($this->healthLead->id, $ids);
    }

    public function test_leads_filter_scope_my_excludes_other_users_leads(): void
    {
        $response = $this->postJson('/api/leads/leads-filter', [
            'scope' => 'my',
            'per_page' => 15,
        ], $this->authHeaders());

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($this->techLead->id, $ids);
        $this->assertContains($this->healthLead->id, $ids);
        $this->assertNotContains($this->otherUserLead->id, $ids);
    }

    public function test_leads_filter_by_date_filter_this_month(): void
    {
        $response = $this->postJson('/api/leads/leads-filter', [
            'scope' => 'all',
            'date_filter' => 'this_month',
            'date_column' => 'created_at',
            'per_page' => 15,
        ], $this->authHeaders());

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($this->techLead->id, $ids);
        $this->assertContains($this->otherUserLead->id, $ids);
        $this->assertNotContains($this->healthLead->id, $ids);
    }

    public function test_leads_filter_requires_authentication(): void
    {
        $response = $this->postJson('/api/leads/leads-filter', [
            'scope' => 'all',
            'per_page' => 15,
        ]);

        $response->assertUnauthorized();
    }

    public function test_filter_options_requires_authentication(): void
    {
        $response = $this->getJson('/api/leads/filter-options');

        $response->assertUnauthorized();
    }
}
