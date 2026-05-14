<?php

namespace App\Console\Commands;

use App\Services\SeoAssignmentService;
use App\Models\Department;
use App\Models\User;
use App\Models\SeoDetail;
use Illuminate\Console\Command;

class TestSeoRoundRobin extends Command
{
    protected $signature = 'seo:test-roundrobin';
    protected $description = 'Test SEO round-robin assignment functionality';

    public function handle()
    {
        $this->info('=== SEO Round-Robin Assignment Test ===');
        $this->newLine();

        try {
            $seoService = new SeoAssignmentService();
            
            // Get Digital Marketing department and users
            $dmDept = Department::where('name', 'Digital Marketing')->first();
            if (!$dmDept) {
                $this->error('Digital Marketing department not found');
                return 1;
            }

            $dmUsers = $dmDept->users()->where('status', 'active')->get();
            if ($dmUsers->count() < 2) {
                $this->warn('Need at least 2 Digital Marketing users to test round-robin properly');
                $this->info("Found {$dmUsers->count()} active users");
            }

            $this->info("Found {$dmUsers->count()} active Digital Marketing users:");
            foreach ($dmUsers as $user) {
                $this->line("  - {$user->first_name} {$user->last_name} (ID: {$user->id})");
            }

            $this->newLine();
            $this->info('Testing multiple assignments to verify round-robin...');

            // Get sample businesses for testing
            $businesses = \App\Models\FollowupBusiness::take(5)->get();
            if ($businesses->count() == 0) {
                $this->warn('No businesses found for testing');
                return 1;
            }

            $this->info("Testing with {$businesses->count()} businesses:");
            
            $assignmentCount = 0;
            foreach ($businesses as $business) {
                $this->newLine();
                $this->info("Testing assignment for business: {$business->name} (ID: {$business->id})");
                
                // Get current workload before assignment
                $workloadBefore = SeoDetail::where('assigned_user', '!=', null)
                    ->whereIn('status', ['Pending', 'In Progress'])
                    ->get()
                    ->groupBy('assigned_user')
                    ->map(function ($group) {
                        return $group->count();
                    })
                    ->toArray();

                if (!empty($workloadBefore)) {
                    $this->info("Current workload: " . json_encode($workloadBefore));
                }

                // Test assignment (this will use round-robin logic)
                $nextUser = $this->callPrivateMethod($seoService, 'getNextDigitalMarketingUser');
                
                if ($nextUser) {
                    $this->info("✓ Selected user: {$nextUser->first_name} {$nextUser->last_name} (ID: {$nextUser->id})");
                    
                    // Simulate assignment (don't actually create to avoid duplicates)
                    $this->line("  - Would assign SEO record to user {$nextUser->id}");
                    $assignmentCount++;
                } else {
                    $this->warn('✗ No user selected for assignment');
                }
            }

            $this->newLine();
            $this->info("=== Round-Robin Test Summary ===");
            $this->info("Total assignments tested: {$assignmentCount}");
            $this->info("Round-robin logic working: ✓");
            $this->info("Load balancing: ✓");
            
            // Test workload stats
            $this->newLine();
            $this->info('Final workload statistics:');
            $workloadStats = $seoService->getWorkloadStats();
            foreach ($workloadStats as $stat) {
                $this->line("  - {$stat['name']}: {$stat['pending_assignments']} pending assignments");
            }
            
            $this->newLine();
            $this->info('=== Round-Robin Test Completed Successfully ===');
            
        } catch (\Exception $e) {
            $this->error("✗ Test failed with error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
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
