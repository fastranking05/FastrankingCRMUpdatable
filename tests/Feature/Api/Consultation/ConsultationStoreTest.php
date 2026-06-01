<?php

namespace Tests\Feature\Api\Consultation;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\FollowupBusiness;
use App\Models\Module;
use App\Models\Role;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ConsultationStoreTest extends TestCase
{
    private User $user;
    private string $token;
    private FollowupBusiness $business;
    private Appointment $appointment;
    private TimeSlot $timeSlot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();

        $suffix = Str::replace('-', '', (string) Str::uuid());
        $shortSuffix = substr($suffix, 0, 12);

        $this->user = User::create([
            'first_name' => 'Consultation',
            'middle_name' => null,
            'last_name' => 'Tester',
            'gender' => 'other',
            'dob' => '1995-01-15',
            'email' => 'consultation_'.$shortSuffix.'@example.test',
            'mobile' => '+91'.$shortSuffix,
            'username' => 'consultation_'.$shortSuffix,
            'password' => bcrypt('password'),
            'date_of_joining' => '2024-01-01',
            'emp_id' => 'EMP'.$shortSuffix,
            'status' => 'active',
            'user_type' => 'employee',
            'designation' => 'Sales Executive',
            'created_by' => null,
        ]);

        $module = Module::create([
            'name' => 'Consultation',
            'description' => 'Consultation module',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $role = Role::create([
            'name' => 'Consultation Creator',
            'description' => 'Can create consultations',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $role->modules()->syncWithoutDetaching([
            $module->id => [
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => false,
            ],
        ]);

        $this->user->roles()->attach($role->id);

        $this->token = JWTAuth::fromUser($this->user);

        $this->business = FollowupBusiness::create([
            'name' => 'Consultation Test Business',
            'category' => 'Technology',
            'type' => 'Enterprise',
            'created_by' => $this->user->id,
        ]);

        $this->timeSlot = TimeSlot::create([
            'name' => 'Morning Slot',
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'duration_minutes' => 30,
            'is_active' => true,
            'max_concurrent_bookings' => 1,
        ]);

        $this->appointment = Appointment::create([
            'id' => 'FRMID00000001',
            'followup_business_id' => $this->business->id,
            'source' => 'Website',
            'status' => 'Appointment Booked',
            'date' => '2026-04-10',
            'time_slot_id' => $this->timeSlot->id,
            'current_status' => 'Booked',
            'created_by' => $this->user->id,
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'comments',
            'consultations',
            'appointments',
            'time_slots',
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
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('created_by');
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
            $table->text('reason')->nullable();
            $table->date('meeting_date')->nullable();
            $table->unsignedBigInteger('meeting_slot')->nullable();
            $table->unsignedBigInteger('closer')->nullable();
            $table->date('conducted_date')->nullable();
            $table->unsignedBigInteger('assigned_user');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('is_customer_available')->default(false);
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

    public function test_store_updates_appointment_current_status_from_consultation_status(): void
    {
        $payload = [
            'appointment_id' => $this->appointment->id,
            'status' => 'scheduled',
            'custom_status' => 'Pending Review',
            'reason' => 'Customer requested consultation',
            'reschedule_date' => '2026-04-15',
            'reschedule_slot' => $this->timeSlot->id,
            'assigned_user' => $this->user->id,
            'conducted_date' => '2026-04-10',
            'is_customer_available' => 1,
            'comments' => [
                [
                    'comment' => 'Initial inquiry received through website contact form. Client interested in enterprise solutions.',
                    'old_status' => null,
                    'new_status' => 'Followup',
                ],
            ],
        ];

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/consultation', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('consultations', [
            'appointment_id' => $this->appointment->id,
            'status' => 'scheduled',
            'custom_status' => 'Pending Review',
            'meeting_date' => '2026-04-15 00:00:00',
            'meeting_slot' => $this->timeSlot->id,
            'assigned_user' => $this->user->id,
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointment->id,
            'current_status' => 'scheduled',
        ]);
    }

    public function test_update_syncs_appointment_current_status_from_consultation_status(): void
    {
        $consultation = Consultation::create([
            'appointment_id' => $this->appointment->id,
            'status' => 'scheduled',
            'assigned_user' => $this->user->id,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/consultation/'.$consultation->id, [
                'status' => 'conducted',
                'custom_status' => 'Completed',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'conducted',
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointment->id,
            'current_status' => 'conducted',
        ]);
    }
}
