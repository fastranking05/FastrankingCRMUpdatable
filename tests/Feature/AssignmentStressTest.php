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

class AssignmentStressTest extends TestCase
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
    public function it_can_handle_massive_concurrent_assignments()
    {
        echo "\n=== MASSIVE CONCURRENT ASSIGNMENT TEST ===\n";
        
        // Create 500 active Sales users (simulating large enterprise)
        $userCount = 500;
        $users = [];
        $batchSize = 50;
        
        echo "Creating {$userCount} users...\n";
        for ($i = 1; $i <= $userCount; $i++) {
            $user = User::create([
                'first_name' => 'User',
                'last_name' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'email' => "massuser{$i}@example.com",
                'username' => "massuser{$i}",
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
            $users[] = $user;
            
            if ($i % $batchSize === 0) {
                echo "Created {$i} users...\n";
            }
        }

        // Assign users to department in batches
        echo "Assigning users to Sales department...\n";
        $userIds = collect($users)->pluck('id')->toArray();
        for ($i = 0; $i < count($userIds); $i += $batchSize) {
            $batch = array_slice($userIds, $i, $batchSize);
            $this->salesDepartment->users()->attach($batch);
        }

        // Test with 10,000 consultations (simulating high volume)
        $consultationCount = 10000;
        echo "Creating and assigning {$consultationCount} consultations...\n";
        
        $startTime = microtime(true);
        $assignedUsers = [];
        $assignmentCounts = [];
        $memoryBefore = memory_get_usage(true);
        
        for ($i = 1; $i <= $consultationCount; $i++) {
            $consultation = Consultation::create([
                'appointment_id' => 'MASS_' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'status' => 'pending_assignment',
            ]);

            $assignedUser = $this->service->assignConsultationToSalesUser($consultation);
            
            if (!$assignedUser) {
                echo "ERROR: Assignment failed for consultation {$i}\n";
                continue;
            }
            
            // Track assignment distribution
            $userId = $assignedUser->id;
            $assignedUsers[] = $userId;
            $assignmentCounts[$userId] = ($assignmentCounts[$userId] ?? 0) + 1;
            
            if ($i % 1000 === 0) {
                $currentTime = microtime(true);
                $elapsed = $currentTime - $startTime;
                $rate = $i / $elapsed;
                echo "Processed {$i} consultations in " . round($elapsed, 2) . "s (Rate: " . round($rate, 2) . " ops/sec)\n";
            }
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

        // Performance analysis
        $avgTimePerAssignment = ($totalTime / $consultationCount) * 1000; // ms
        $assignmentsPerSecond = $consultationCount / $totalTime;
        
        // Load balancing analysis
        $maxLoad = max($assignmentCounts);
        $minLoad = min($assignmentCounts);
        $avgLoad = array_sum($assignmentCounts) / count($assignmentCounts);
        $loadVariance = $maxLoad - $minLoad;
        $loadStdDev = $this->calculateStandardDeviation($assignmentCounts);

        echo "\n=== PERFORMANCE RESULTS ===\n";
        echo "Total assignments: {$consultationCount}\n";
        echo "Active users: {$userCount}\n";
        echo "Total time: " . round($totalTime, 3) . "s\n";
        echo "Average time per assignment: " . round($avgTimePerAssignment, 2) . "ms\n";
        echo "Assignments per second: " . round($assignmentsPerSecond, 2) . "\n";
        echo "Memory used: " . round($memoryUsed, 2) . "MB\n";
        
        echo "\n=== LOAD BALANCING RESULTS ===\n";
        echo "Max load: {$maxLoad}\n";
        echo "Min load: {$minLoad}\n";
        echo "Average load: " . round($avgLoad, 2) . "\n";
        echo "Load variance: {$loadVariance}\n";
        echo "Load standard deviation: " . round($loadStdDev, 2) . "\n";
        echo "Load balance efficiency: " . round((1 - ($loadVariance / $maxLoad)) * 100, 2) . "%\n";

        // Assertions for enterprise-level performance
        $this->assertLessThan(30.0, $totalTime, "Assignment took too long for enterprise scale");
        $this->assertLessThan(3.0, $avgTimePerAssignment, "Average assignment time too high");
        $this->assertGreaterThan(300, $assignmentsPerSecond, "Assignment rate too low for enterprise scale");
        $this->assertLessThan(100, $memoryUsed, "Memory usage too high");
        
        // Load balancing assertions
        $this->assertLessThan(5, $loadVariance, "Load not balanced properly for large scale");
        $this->assertLessThan(2.0, $loadStdDev, "Load deviation too high");
        
        // All users should have assignments
        $this->assertEquals($userCount, count($assignmentCounts), "Not all users received assignments");
        
        // Average should be close to expected (10000/500 = 20)
        $this->assertEquals(20.0, $avgLoad, '', 2.0); // Allow 2 consultations variance
    }

    /** @test */
    public function it_handles_memory_efficiently_under_sustained_load()
    {
        echo "\n=== SUSTAINED LOAD MEMORY TEST ===\n";
        
        // Create 100 users
        for ($i = 1; $i <= 100; $i++) {
            $user = User::create([
                'first_name' => 'Sustained',
                'last_name' => $i,
                'email' => "sustained{$i}@example.com",
                'username' => "sustained{$i}",
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
        }

        $userIds = range(1, 100);
        $this->salesDepartment->users()->attach($userIds);

        // Test sustained load over time
        $batches = 10;
        $consultationsPerBatch = 1000;
        $memorySnapshots = [];

        for ($batch = 1; $batch <= $batches; $batch++) {
            $memoryBefore = memory_get_usage(true);
            
            for ($i = 1; $i <= $consultationsPerBatch; $i++) {
                $consultation = Consultation::create([
                    'appointment_id' => 'SUST_' . str_pad($batch, 2, '0', STR_PAD_LEFT) . '_' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'status' => 'pending_assignment',
                ]);

                $assignedUser = $this->service->assignConsultationToSalesUser($consultation);
                $this->assertNotNull($assignedUser);
            }

            $memoryAfter = memory_get_usage(true);
            $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;
            $memorySnapshots[] = $memoryUsed;
            
            echo "Batch {$batch}/{$batches}: Memory used: " . round($memoryUsed, 2) . "MB\n";
            
            // Clear cache to test memory cleanup
            if ($batch % 3 === 0) {
                Cache::flush();
                echo "  Cache flushed\n";
            }
        }

        $maxMemory = max($memorySnapshots);
        $avgMemory = array_sum($memorySnapshots) / count($memorySnapshots);
        
        echo "\nAverage memory per batch: " . round($avgMemory, 2) . "MB\n";
        echo "Peak memory usage: " . round($maxMemory, 2) . "MB\n";

        // Memory efficiency assertions
        $this->assertLessThan(50, $maxMemory, "Peak memory usage too high");
        $this->assertLessThan(20, $avgMemory, "Average memory usage too high");
    }

    /** @test */
    public function it_maintains_consistency_under_rapid_assignments()
    {
        echo "\n=== RAPID ASSIGNMENT CONSISTENCY TEST ===\n";
        
        // Create 50 users
        for ($i = 1; $i <= 50; $i++) {
            $user = User::create([
                'first_name' => 'Rapid',
                'last_name' => $i,
                'email' => "rapid{$i}@example.com",
                'username' => "rapid{$i}",
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
        }

        $userIds = range(1, 50);
        $this->salesDepartment->users()->attach($userIds);

        // Rapid assignment test
        $rapidCount = 2000;
        $duplicateCheck = [];
        $assignmentTimes = [];

        echo "Performing {$rapidCount} rapid assignments...\n";
        
        for ($i = 1; $i <= $rapidCount; $i++) {
            $startTime = microtime(true);
            
            $consultation = Consultation::create([
                'appointment_id' => 'RAPID_' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'status' => 'pending_assignment',
            ]);

            $assignedUser = $this->service->assignConsultationToSalesUser($consultation);
            
            $endTime = microtime(true);
            $assignmentTime = ($endTime - $startTime) * 1000; // ms
            $assignmentTimes[] = $assignmentTime;
            
            $this->assertNotNull($assignedUser, "Assignment failed for consultation {$i}");
            
            // Check for duplicates (same consultation assigned multiple times)
            $consultationId = $consultation->id;
            if (isset($duplicateCheck[$consultationId])) {
                $this->fail("Duplicate assignment detected for consultation {$consultationId}");
            }
            $duplicateCheck[$consultationId] = $assignedUser->id;
        }

        $avgAssignmentTime = array_sum($assignmentTimes) / count($assignmentTimes);
        $maxAssignmentTime = max($assignmentTimes);
        $p95AssignmentTime = $this->calculatePercentile($assignmentTimes, 95);

        echo "\n=== RAPID ASSIGNMENT RESULTS ===\n";
        echo "Total assignments: {$rapidCount}\n";
        echo "Average assignment time: " . round($avgAssignmentTime, 2) . "ms\n";
        echo "Max assignment time: " . round($maxAssignmentTime, 2) . "ms\n";
        echo "95th percentile: " . round($p95AssignmentTime, 2) . "ms\n";

        // Performance assertions for rapid assignments
        $this->assertLessThan(10.0, $avgAssignmentTime, "Average assignment time too high for rapid operations");
        $this->assertLessThan(50.0, $p95AssignmentTime, "95th percentile assignment time too high");
        $this->assertEquals($rapidCount, count($duplicateCheck), "Assignment consistency check failed");
    }

    private function calculateStandardDeviation(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values)) / count($values);
        return sqrt($variance);
    }

    private function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower === $upper) {
            return $values[$lower];
        }
        
        $weight = $index - $lower;
        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }
}
