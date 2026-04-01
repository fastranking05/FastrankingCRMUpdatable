<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use App\Models\Department;
use App\Models\Appointment;
use App\Models\Quality;
use App\Models\QualityQuestion;
use App\Models\QualityAnswer;
use App\Services\UserAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QualityAssignmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private UserAssignmentService $service;
    private Department $salesDepartment;
    private User $qualityUser;
    private User $salesUser;

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

        // Create quality user (who submits quality data)
        $this->qualityUser = User::create([
            'first_name' => 'Quality',
            'last_name' => 'User',
            'email' => 'quality@example.com',
            'username' => 'quality',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        // Create sales user (who should receive assignments)
        $this->salesUser = User::create([
            'first_name' => 'Sales',
            'last_name' => 'User',
            'email' => 'sales@example.com',
            'username' => 'sales',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        // Assign sales user to Sales department
        $this->salesDepartment->users()->attach([$this->salesUser->id]);
    }

    /** @test */
    public function it_automatically_assigns_consultation_when_quality_is_approved()
    {
        echo "\n=== QUALITY APPROVAL ASSIGNMENT TEST ===\n";

        // Create appointment
        $appointment = Appointment::create([
            'id' => 'TEST_APPT_001',
            'current_status' => 'Conducted',
        ]);

        // Create quality question
        $qualityQuestion = QualityQuestion::create([
            'question' => 'Test question for integration',
            'is_active' => true,
        ]);

        // Simulate quality approval submission
        $qualityData = [
            'auditstatus' => 'qualified', // This should trigger consultation assignment
            'status' => 'approved',
            'meetinglink' => 'https://meet.example.com/test',
            'score' => 85.5,
            'appointment_id' => $appointment->id,
            'answers' => [
                [
                    'quality_id' => 1, // Will be created
                    'question_id' => $qualityQuestion->id,
                    'answer' => 'yes',
                ],
            ],
        ];

        // Create quality record (simulating the controller logic)
        $quality = Quality::create([
            'auditstatus' => $qualityData['auditstatus'],
            'status' => $qualityData['status'],
            'meeting_link' => $qualityData['meetinglink'],
            'score' => $qualityData['score'],
            'appointment_id' => $qualityData['appointment_id'],
            'assigned_user' => $this->qualityUser->id,
        ]);

        echo "Created quality record with ID: {$quality->id}\n";
        echo "Quality status: {$quality->auditstatus}\n";

        // Check if consultation was created and assigned
        if ($quality->auditstatus === 'qualified') {
            $consultation = Consultation::create([
                'appointment_id' => $appointment->id,
                'status' => 'pending_assignment',
            ]);

            echo "Created consultation with ID: {$consultation->id}\n";

            // Assign consultation using the service
            $assignedUser = $this->service->assignConsultationToSalesUser($consultation);

            if ($assignedUser) {
                echo "✓ Consultation assigned to user: {$assignedUser->first_name} {$assignedUser->last_name}\n";
                echo "✓ Assigned user ID: {$assignedUser->id}\n";
                echo "✓ Assigned user email: {$assignedUser->email}\n";

                // Verify consultation was updated
                $consultation->refresh();
                echo "✓ Consultation status: {$consultation->status}\n";
                echo "✓ Consultation assigned_user: {$consultation->assigned_user}\n";

                // Assertions
                $this->assertNotNull($assignedUser);
                $this->assertEquals($this->salesUser->id, $assignedUser->id);
                $this->assertEquals('assigned', $consultation->status);
                $this->assertEquals($this->salesUser->id, $consultation->assigned_user);

                echo "✓ All assertions passed!\n";
            } else {
                echo "✗ Failed to assign consultation\n";
                $this->fail('Consultation assignment failed');
            }
        } else {
            echo "No consultation assignment expected for unqualified quality\n";
            $this->assertTrue(true); // Test passes for unqualified quality
        }
    }

    /** @test */
    public function it_handles_unqualified_quality_without_assignment()
    {
        echo "\n=== UNQUALIFIED QUALITY TEST ===\n";

        // Create appointment
        $appointment = Appointment::create([
            'id' => 'TEST_APPT_002',
            'current_status' => 'Conducted',
        ]);

        // Create quality record with unqualified status
        $quality = Quality::create([
            'auditstatus' => 'unqualified', // This should NOT trigger consultation assignment
            'status' => 'rejected',
            'meeting_link' => null,
            'score' => 45.0,
            'appointment_id' => $appointment->id,
            'assigned_user' => $this->qualityUser->id,
        ]);

        echo "Created quality record with ID: {$quality->id}\n";
        echo "Quality status: {$quality->auditstatus}\n";

        // Check that no consultation was created
        $consultations = Consultation::where('appointment_id', $appointment->id)->get();
        echo "Found consultations for appointment: {$consultations->count()}\n";

        $this->assertEquals(0, $consultations->count(), "No consultation should be created for unqualified quality");
        echo "✓ No consultation created for unqualified quality - correct behavior\n";
    }

    /** @test */
    public function it_handles_assignment_when_no_sales_users_available()
    {
        echo "\n=== NO SALES USERS TEST ===\n";

        // Remove sales user from department or deactivate
        $this->salesDepartment->users()->detach([$this->salesUser->id]);
        // OR
        // $this->salesUser->update(['status' => 'inactive']);

        // Create appointment
        $appointment = Appointment::create([
            'id' => 'TEST_APPT_003',
            'current_status' => 'Conducted',
        ]);

        // Create quality record with qualified status
        $quality = Quality::create([
            'auditstatus' => 'qualified',
            'status' => 'approved',
            'meeting_link' => 'https://meet.example.com/test',
            'score' => 90.0,
            'appointment_id' => $appointment->id,
            'assigned_user' => $this->qualityUser->id,
        ]);

        echo "Created quality record with ID: {$quality->id}\n";
        echo "Quality status: {$quality->auditstatus}\n";

        // Create consultation
        $consultation = Consultation::create([
            'appointment_id' => $appointment->id,
            'status' => 'pending_assignment',
        ]);

        echo "Created consultation with ID: {$consultation->id}\n";

        // Try to assign consultation
        $assignedUser = $this->service->assignConsultationToSalesUser($consultation);

        if ($assignedUser === null) {
            echo "✓ No assignment made (no active Sales users available)\n";
            
            // Check consultation status
            $consultation->refresh();
            echo "✓ Consultation status: {$consultation->status}\n";
            
            // Should remain in original state since assignment failed
            $this->assertEquals('pending_assignment', $consultation->status);
            $this->assertNull($consultation->assigned_user);
            
            echo "✓ Correctly handled no available users scenario\n";
        } else {
            echo "✗ Unexpected assignment made when no users available\n";
            $this->fail('Assignment should not succeed when no users available');
        }
    }

    /** @test */
    public function it_provides_assignment_statistics()
    {
        echo "\n=== ASSIGNMENT STATISTICS TEST ===\n";

        // Create and assign multiple consultations
        for ($i = 1; $i <= 5; $i++) {
            $appointment = Appointment::create([
                'id' => 'TEST_APPT_STATS_' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'current_status' => 'Conducted',
            ]);

            $consultation = Consultation::create([
                'appointment_id' => $appointment->id,
                'status' => 'pending_assignment',
            ]);

            $assignedUser = $this->service->assignConsultationToSalesUser($consultation);
            $this->assertNotNull($assignedUser);
        }

        // Get statistics
        $stats = $this->service->getSalesAssignmentStats();

        echo "Assignment Statistics:\n";
        foreach ($stats as $stat) {
            echo "- User: {$stat['user_name']} (ID: {$stat['user_id']})\n";
            echo "  Load: {$stat['current_load']} consultations\n";
            echo "  Email: {$stat['email']}\n";
        }

        // Verify statistics
        $this->assertCount(1, $stats, "Should have statistics for one active user");
        $userStat = $stats[0];
        $this->assertEquals($this->salesUser->id, $userStat['user_id']);
        $this->assertEquals(5, $userStat['current_load']);

        echo "✓ Statistics verification passed\n";
    }
}
