<?php

namespace App\Console\Commands;

use App\Services\SeoAssignmentService;
use App\Models\Department;
use App\Models\User;
use App\Models\SeoDetail;
use Illuminate\Console\Command;

class TestSeoAssignment extends Command
{
    protected $signature = 'seo:test-assignment';
    protected $description = 'Test SEO assignment functionality';

    public function handle()
    {
        $this->info('=== SEO Assignment Service Test ===');
        $this->newLine();

        try {
            // Test 1: Check if Digital Marketing department exists
            $this->info('1. Checking Digital Marketing department...');
            $dmDept = Department::where('name', 'Digital Marketing')->first();
            if ($dmDept) {
                $this->info("✓ Digital Marketing department found (ID: {$dmDept->id})");
                
                // Get active users in Digital Marketing
                $dmUsers = $dmDept->users()->where('status', 'active')->get();
                $this->info("✓ Found {$dmUsers->count()} active users in Digital Marketing department");
                
                foreach ($dmUsers as $user) {
                    $this->line("  - User: {$user->first_name} {$user->last_name} (ID: {$user->id})");
                }
            } else {
                $this->error('✗ Digital Marketing department not found');
            }
            
            $this->newLine();
            
            // Test 2: Check existing SEO details
            $this->info('2. Checking existing SEO details...');
            $seoDetailsCount = SeoDetail::count();
            $this->info("✓ Found {$seoDetailsCount} existing SEO detail records");
            
            // Test 3: Test service instantiation
            $this->newLine();
            $this->info('3. Testing SeoAssignmentService instantiation...');
            $seoService = new SeoAssignmentService();
            $this->info('✓ SeoAssignmentService instantiated successfully');
            
            // Test 4: Test workload stats
            $this->newLine();
            $this->info('4. Testing workload statistics...');
            $workloadStats = $seoService->getWorkloadStats();
            if (!empty($workloadStats)) {
                $this->info('✓ Workload stats retrieved:');
                foreach ($workloadStats as $stat) {
                    $this->line("  - {$stat['name']} (ID: {$stat['user_id']}): {$stat['pending_assignments']} pending assignments");
                }
            } else {
                $this->warn('✗ No workload stats available');
            }
            
            // Test 5: Test assignment logic (if we have Digital Marketing users)
            if ($dmDept && $dmDept->users()->where('status', 'active')->exists()) {
                $this->newLine();
                $this->info('5. Testing assignment logic...');
                
                // Get a sample business ID for testing
                $sampleBusiness = \App\Models\FollowupBusiness::first();
                if ($sampleBusiness) {
                    $this->info("Testing assignment for business ID: {$sampleBusiness->id}");
                    
                    // Test the assignment method
                    $nextUser = $this->callPrivateMethod($seoService, 'getNextDigitalMarketingUser');
                    if ($nextUser) {
                        $this->info("✓ Next user selected: {$nextUser->first_name} {$nextUser->last_name} (ID: {$nextUser->id})");
                    } else {
                        $this->warn('✗ No user selected for assignment');
                    }
                } else {
                    $this->warn('✗ No followup businesses found for testing');
                }
            }
            
            $this->newLine();
            $this->info('=== Test Completed Successfully ===');
            
        } catch (\Exception $e) {
            $this->error("✗ Test failed with error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
        
        return 0;
    }
    
    private function callPrivateMethod($object, $method)
    {
        $reflection = new \ReflectionClass($object);
        $privateMethod = $reflection->getMethod($method);
        $privateMethod->setAccessible(true);
        return $privateMethod->invoke($object);
    }
}
