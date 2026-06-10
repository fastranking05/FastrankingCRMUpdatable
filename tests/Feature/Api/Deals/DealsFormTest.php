<?php

namespace Tests\Feature\Api\Deals;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupBusiness;
use App\Models\Module;
use App\Models\Role;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DealsFormTest extends TestCase
{
    private User $user;
    private string $token;
    private FollowupBusiness $eligibleBusiness;
    private FollowupBusiness $otherBusiness;
    private FollowupAuthPerson $authPerson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();

        $suffix = Str::replace('-', '', (string) Str::uuid());
        $shortSuffix = substr($suffix, 0, 12);

        $this->user = User::create([
            'first_name' => 'Deals',
            'middle_name' => null,
            'last_name' => 'Form',
            'gender' => 'other',
            'dob' => '1995-01-15',
            'email' => 'deals_form_'.$shortSuffix.'@example.test',
            'mobile' => '+91'.$shortSuffix,
            'username' => 'deals_form_'.$shortSuffix,
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
            'name' => 'Deals Creator',
            'description' => 'Can create deals',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $role->modules()->syncWithoutDetaching([
            $module->id => [
                'can_create' => true,
                'can_read' => true,
                'can_update' => false,
                'can_delete' => false,
            ],
        ]);

        $this->user->roles()->attach($role->id);
        $this->token = JWTAuth::fromUser($this->user);

        $this->eligibleBusiness = FollowupBusiness::create([
            'name' => 'Eligible Business',
            'category' => 'Technology',
            'type' => 'Enterprise',
            'created_by' => $this->user->id,
        ]);

        $this->otherBusiness = FollowupBusiness::create([
            'name' => 'Other Business',
            'category' => 'Retail',
            'type' => 'SMB',
            'created_by' => $this->user->id,
        ]);

        $this->authPerson = FollowupAuthPerson::create([
            'firstname' => 'David',
            'lastname' => 'Chen',
            'primaryemail' => 'david@eligible.test',
            'primarymobile' => '+1 555-0101',
        ]);

        $this->eligibleBusiness->authPersons()->attach($this->authPerson->id);

        $timeSlot = TimeSlot::create([
            'name' => 'Morning Slot',
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'duration_minutes' => 30,
            'is_active' => true,
            'max_concurrent_bookings' => 1,
        ]);

        $eligibleAppointment = Appointment::create([
            'id' => 'FRMID00000001',
            'followup_business_id' => $this->eligibleBusiness->id,
            'source' => 'Website',
            'status' => 'Appointment Booked',
            'date' => '2026-04-10',
            'time_slot_id' => $timeSlot->id,
            'current_status' => 'conducted',
            'created_by' => $this->user->id,
        ]);

        $otherAppointment = Appointment::create([
            'id' => 'FRMID00000002',
            'followup_business_id' => $this->otherBusiness->id,
            'source' => 'Referral',
            'status' => 'Appointment Booked',
            'date' => '2026-04-11',
            'time_slot_id' => $timeSlot->id,
            'current_status' => 'scheduled',
            'created_by' => $this->user->id,
        ]);

        Consultation::create([
            'appointment_id' => $eligibleAppointment->id,
            'status' => 'conducted',
            'custom_status' => 'Conducted Offered',
            'assigned_user' => $this->user->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Consultation::create([
            'appointment_id' => $otherAppointment->id,
            'status' => 'scheduled',
            'custom_status' => 'Pending',
            'assigned_user' => $this->user->id,
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'followup_business_auth_person',
            'consultations',
            'appointments',
            'time_slots',
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

        Schema::create('followup_business_auth_person', function (Blueprint $table) {
            $table->unsignedBigInteger('followup_business_id');
            $table->unsignedBigInteger('followup_auth_person_id');
            $table->timestamps();
        });

        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->boolean('is_active')->default(true);
            $table->integer('max_concurrent_bookings')->default(1);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('followup_business_id');
            $table->string('source')->nullable();
            $table->string('status');
            $table->date('date');
            $table->unsignedBigInteger('time_slot_id');
            $table->string('current_status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_id');
            $table->string('status');
            $table->string('custom_status')->nullable();
            $table->unsignedBigInteger('assigned_user');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function test_form_businesses_returns_only_conducted_offered_businesses(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/deals/form/businesses');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Eligible Business', $names);
        $this->assertNotContains('Other Business', $names);
    }

    public function test_form_business_auth_persons_returns_contacts_for_eligible_business(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/deals/form/businesses/'.$this->eligibleBusiness->id.'/auth-persons');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'David Chen')
            ->assertJsonPath('data.0.email', 'david@eligible.test');
    }

    public function test_form_business_auth_persons_rejects_ineligible_business(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/deals/form/businesses/'.$this->otherBusiness->id.'/auth-persons');

        $response->assertNotFound();
    }
}
