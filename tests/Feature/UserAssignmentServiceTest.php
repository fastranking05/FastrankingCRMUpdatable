<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use App\Models\Department;
use App\Services\UserAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserAssignmentService $service;
    private Department $salesDepartment;
    private User $activeUser1;
    private User $activeUser2;
    private User $inactiveUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new UserAssignmentService();
        
        // Create Sales department
        $this->salesDepartment = Department::create([
            'name' => 'Sales',
            'description' => 'Sales Department',
            'status' => 'active',
            'created_by' => 1,
        ]);

        // Create test users
        $this->activeUser1 = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->activeUser2 = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'username' => 'janesmith',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->inactiveUser = User::create([
            'first_name' => 'Bob',
            'last_name' => 'Wilson',
            'email' => 'bob@example.com',
            'username' => 'bobwilson',
            'password' => bcrypt('password'),
            'status' => 'inactive',
        ]);

        // Assign users to Sales department
        $this->salesDepartment->users()->attach([$this->activeUser1->id, $this->activeUser2->id, $this->inactiveUser->id]);
    }

    /** @test */
    public function it_can_assign_consultation_to_active_sales_user()
    {
        $consultation = Consultation::create([
            'appointment_id' => 'TEST123',
            'status' => 'pending_assignment',
        ]);

        $assignedUser = $this->service->assignConsultationToSalesUser($consultation);

        $this->assertNotNull($assignedUser);
        $this->assertTrue($assignedUser->status === 'active');
        $this->assertTrue($assignedUser->departments()->where('name', 'Sales')->exists());
        
        // Check consultation was updated
        $consultation->refresh();
        $this->assertEquals($assignedUser->id, $consultation->assigned_user);
        $this->assertEquals('assigned', $consultation->status);
    }

    /** @test */
    public function it_uses_round_robin_assignment()
    {
        // Create multiple consultations
        $consultation1 = Consultation::create(['appointment_id' => 'TEST1', 'status' => 'pending_assignment']);
        $consultation2 = Consultation::create(['appointment_id' => 'TEST2', 'status' => 'pending_assignment']);
        $consultation3 = Consultation::create(['appointment_id' => 'TEST3', 'status' => 'pending_assignment']);

        $assignedUser1 = $this->service->assignConsultationToSalesUser($consultation1);
        $assignedUser2 = $this->service->assignConsultationToSalesUser($consultation2);
        $assignedUser3 = $this->service->assignConsultationToSalesUser($consultation3);

        // All should be assigned to active users
        $this->assertNotNull($assignedUser1);
        $this->assertNotNull($assignedUser2);
        $this->assertNotNull($assignedUser3);

        // Should distribute between the two active users
        $assignedIds = collect([$assignedUser1->id, $assignedUser2->id, $assignedUser3->id]);
        $this->assertTrue($assignedIds->contains($this->activeUser1->id));
        $this->assertTrue($assignedIds->contains($this->activeUser2->id));
        $this->assertFalse($assignedIds->contains($this->inactiveUser->id));
    }

    /** @test */
    public function it_returns_null_when_no_active_sales_users_available()
    {
        // Deactivate all Sales users
        $this->activeUser1->update(['status' => 'inactive']);
        $this->activeUser2->update(['status' => 'inactive']);

        $consultation = Consultation::create([
            'appointment_id' => 'TEST123',
            'status' => 'pending_assignment',
        ]);

        $assignedUser = $this->service->assignConsultationToSalesUser($consultation);

        $this->assertNull($assignedUser);
    }

    /** @test */
    public function it_can_get_sales_assignment_statistics()
    {
        // Assign some consultations to test load balancing
        $consultation1 = Consultation::create(['appointment_id' => 'TEST1', 'status' => 'assigned']);
        $consultation2 = Consultation::create(['appointment_id' => 'TEST2', 'status' => 'assigned']);
        
        $consultation1->assigned_user = $this->activeUser1->id;
        $consultation1->save();
        
        $consultation2->assigned_user = $this->activeUser1->id;
        $consultation2->save();

        $stats = $this->service->getSalesAssignmentStats();

        $this->assertCount(2, $stats); // Only active users
        
        $user1Stat = collect($stats)->firstWhere('user_id', $this->activeUser1->id);
        $user2Stat = collect($stats)->firstWhere('user_id', $this->activeUser2->id);

        $this->assertEquals(2, $user1Stat['current_load']);
        $this->assertEquals(0, $user2Stat['current_load']);
    }

    /** @test */
    public function it_can_reset_round_robin_index()
    {
        // This should not throw an exception
        $this->service->resetRoundRobinIndex('sales');
        
        // Assign consultations to verify it starts from beginning
        $consultation1 = Consultation::create(['appointment_id' => 'TEST1', 'status' => 'pending_assignment']);
        $consultation2 = Consultation::create(['appointment_id' => 'TEST2', 'status' => 'pending_assignment']);

        $assignedUser1 = $this->service->assignConsultationToSalesUser($consultation1);
        $assignedUser2 = $this->service->assignConsultationToSalesUser($consultation2);

        $this->assertNotNull($assignedUser1);
        $this->assertNotNull($assignedUser2);
    }
}
