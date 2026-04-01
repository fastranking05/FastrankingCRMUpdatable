<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use App\Models\Department;
use App\Services\UserAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class UserAssignmentPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private UserAssignmentService $service;
    private Department $salesDepartment;

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
    }

    /** @test */
    public function it_can_handle_large_number_of_concurrent_assignments()
    {
        // Create 100 active Sales users (simulating large team)
        $users = [];
        for ($i = 1; $i <= 100; $i++) {
            $user = User::create([
                'first_name' => 'User',
                'last_name' => $i,
                'email' => "user{$i}@example.com",
                'username' => "user{$i}",
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
            $users[] = $user;
        }

        // Assign all users to Sales department
        $userIds = collect($users)->pluck('id')->toArray();
        $this->salesDepartment->users()->attach($userIds);

        // Test performance with 1000 consultations
        $startTime = microtime(true);
        $assignedUsers = [];
        $assignmentCounts = [];

        for ($i = 1; $i <= 1000; $i++) {
            $consultation = Consultation::create([
                'appointment_id' => 'PERF_TEST_' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'status' => 'pending_assignment',
            ]);

            $assignedUser = $this->service->assignConsultationToSalesUser($consultation);
            
            $this->assertNotNull($assignedUser, "Assignment failed for consultation {$i}");
            
            // Track assignment distribution
            $userId = $assignedUser->id;
            $assignedUsers[] = $userId;
            $assignmentCounts[$userId] = ($assignmentCounts[$userId] ?? 0) + 1;
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(5.0, $totalTime, "Assignment took too long: {$totalTime}s for 1000 consultations");
        
        // Load balancing assertions - should be roughly distributed
        $maxLoad = max($assignmentCounts);
        $minLoad = min($assignmentCounts);
        $loadVariance = $maxLoad - $minLoad;
        
        $this->assertLessThan(3, $loadVariance, "Load not balanced properly. Max: {$maxLoad}, Min: {$minLoad}");
        
        // All users should have some assignments (roughly equal distribution)
        $this->assertCount(100, $assignmentCounts, "Not all users received assignments");
        
        // Average should be around 10 consultations per user (1000/100)
        $averageLoad = array_sum($assignmentCounts) / count($assignmentCounts);
        $this->assertEquals(10.0, $averageLoad, '', 1.0); // Allow 1 consultation variance

        echo "\nPerformance Test Results:\n";
        echo "- Total assignments: 1000\n";
        echo "- Active users: 100\n";
        echo "- Total time: " . round($totalTime, 3) . "s\n";
        echo "- Average time per assignment: " . round(($totalTime / 1000) * 1000, 2) . "ms\n";
        echo "- Load variance: {$loadVariance}\n";
        echo "- Average load per user: " . round($averageLoad, 2) . "\n";
    }

    /** @test */
    public function it_handles_cache_efficiently_under_load()
    {
        // Create 50 active Sales users
        $users = [];
        for ($i = 1; $i <= 50; $i++) {
            $user = User::create([
                'first_name' => 'User',
                'last_name' => $i,
                'email' => "cacheuser{$i}@example.com",
                'username' => "cacheuser{$i}",
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
            $users[] = $user;
        }

        $userIds = collect($users)->pluck('id')->toArray();
        $this->salesDepartment->users()->attach($userIds);

        // Clear cache to start fresh
        Cache::flush();

        // Test cache performance
        $startTime = microtime(true);
        $cacheHits = 0;
        $cacheMisses = 0;

        for ($i = 1; $i <= 500; $i++) {
            $consultation = Consultation::create([
                'appointment_id' => 'CACHE_TEST_' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'status' => 'pending_assignment',
            ]);

            $assignedUser = $this->service->assignConsultationToSalesUser($consultation);
            $this->assertNotNull($assignedUser);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Should be very fast with caching
        $this->assertLessThan(2.0, $totalTime, "Cached assignment took too long: {$totalTime}s for 500 consultations");

        echo "\nCache Performance Test Results:\n";
        echo "- Total assignments: 500\n";
        echo "- Active users: 50\n";
        echo "- Total time: " . round($totalTime, 3) . "s\n";
        echo "- Average time per assignment: " . round(($totalTime / 500) * 1000, 2) . "ms\n";
    }

    /** @test */
    public function it_maintains_consistency_under_concurrent_load()
    {
        // Create 20 active Sales users
        $users = [];
        for ($i = 1; $i <= 20; $i++) {
            $user = User::create([
                'first_name' => 'User',
                'last_name' => $i,
                'email' => "concurrent{$i}@example.com",
                'username' => "concurrent{$i}",
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
            $users[] = $user;
        }

        $userIds = collect($users)->pluck('id')->toArray();
        $this->salesDepartment->users()->attach($userIds);

        // Simulate concurrent assignments
        $consultations = [];
        for ($i = 1; $i <= 200; $i++) {
            $consultation = Consultation::create([
                'appointment_id' => 'CONCURRENT_' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'status' => 'pending_assignment',
            ]);
            $consultations[] = $consultation;
        }

        // Process assignments in random order to simulate concurrency
        shuffle($consultations);
        $assignedUsers = [];

        foreach ($consultations as $consultation) {
            $assignedUser = $this->service->assignConsultationToSalesUser($consultation);
            $this->assertNotNull($assignedUser);
            $assignedUsers[] = $assignedUser->id;
        }

        // Verify all consultations were assigned
        $this->assertCount(200, $assignedUsers);
        
        // Verify no duplicate assignments for same consultation
        $uniqueAssignments = array_unique($assignedUsers);
        $this->assertCount(200, $uniqueAssignments, "Duplicate assignments detected");

        // Verify load distribution
        $assignmentCounts = array_count_values($assignedUsers);
        $maxLoad = max($assignmentCounts);
        $minLoad = min($assignmentCounts);
        $loadVariance = $maxLoad - $minLoad;
        
        $this->assertLessThan(3, $loadVariance, "Load distribution inconsistent under concurrent load");

        echo "\nConcurrency Test Results:\n";
        echo "- Total assignments: 200\n";
        echo "- Active users: 20\n";
        echo "- Load variance: {$loadVariance}\n";
        echo "- Max load: {$maxLoad}\n";
        echo "- Min load: {$minLoad}\n";
    }

    /** @test */
    public function it_handles_edge_cases_gracefully()
    {
        // Test with no active users
        $inactiveUser = User::create([
            'first_name' => 'Inactive',
            'last_name' => 'User',
            'email' => 'inactive@example.com',
            'username' => 'inactive',
            'password' => bcrypt('password'),
            'status' => 'inactive',
        ]);

        $this->salesDepartment->users()->attach([$inactiveUser->id]);

        $consultation = Consultation::create([
            'appointment_id' => 'EDGE_CASE_1',
            'status' => 'pending_assignment',
        ]);

        $assignedUser = $this->service->assignConsultationToSalesUser($consultation);
        $this->assertNull($assignedUser);

        // Test with single active user
        $activeUser = User::create([
            'first_name' => 'Single',
            'last_name' => 'Active',
            'email' => 'single@example.com',
            'username' => 'single',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->salesDepartment->users()->attach([$activeUser->id]);

        $consultation2 = Consultation::create([
            'appointment_id' => 'EDGE_CASE_2',
            'status' => 'pending_assignment',
        ]);

        $assignedUser2 = $this->service->assignConsultationToSalesUser($consultation2);
        $this->assertNotNull($assignedUser2);
        $this->assertEquals($activeUser->id, $assignedUser2->id);

        echo "\nEdge Case Test Results:\n";
        echo "- No active users: " . ($assignedUser === null ? "PASS" : "FAIL") . "\n";
        echo "- Single active user: " . ($assignedUser2 !== null ? "PASS" : "FAIL") . "\n";
    }
}
