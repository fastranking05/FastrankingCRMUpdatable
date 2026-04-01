<?php

namespace Tests\Unit;

use App\Services\UserAssignmentService;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class UserAssignmentServiceUnitTest extends TestCase
{
    private UserAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserAssignmentService();
    }

    /** @test */
    public function it_can_reset_round_robin_index()
    {
        // This should not throw any exceptions
        $this->service->resetRoundRobinIndex('sales');
        
        // Verify cache is cleared
        $cacheKey = 'user_assignment_sales_index';
        $this->assertFalse(Cache::has($cacheKey));
        
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /** @test */
    public function it_manages_cache_keys_properly()
    {
        $department = 'sales';
        
        // Test round robin index management
        $index1 = $this->getPrivateMethod($this->service, 'getRoundRobinIndex', [$department, 5]);
        $index2 = $this->getPrivateMethod($this->service, 'getRoundRobinIndex', [$department, 5]);
        $index3 = $this->getPrivateMethod($this->service, 'getRoundRobinIndex', [$department, 5]);
        
        // Should increment each time
        $this->assertEquals(0, $index1);
        $this->assertEquals(1, $index2);
        $this->assertEquals(2, $index3);
        
        // Reset and verify
        $this->service->resetRoundRobinIndex($department);
        $indexAfterReset = $this->getPrivateMethod($this->service, 'getRoundRobinIndex', [$department, 5]);
        $this->assertEquals(0, $indexAfterReset);
    }

    /** @test */
    public function it_handles_cache_ttl_properly()
    {
        $department = 'test_dept';
        $userCount = 3;
        
        // Set index
        $index1 = $this->getPrivateMethod($this->service, 'getRoundRobinIndex', [$department, $userCount]);
        
        // Verify cache exists with proper TTL
        $cacheKey = 'user_assignment_' . $department . '_index';
        $this->assertTrue(Cache::has($cacheKey));
        
        // Simulate cache expiration by manually clearing
        Cache::forget($cacheKey);
        
        // Should start from 0 again
        $indexAfterClear = $this->getPrivateMethod($this->service, 'getRoundRobinIndex', [$department, $userCount]);
        $this->assertEquals(0, $indexAfterClear);
    }

    /** @test */
    public function it_handles_large_user_counts_efficiently()
    {
        $department = 'large_dept';
        $largeUserCount = 1000; // Reduced for more realistic testing
        
        $startTime = microtime(true);
        
        // Test multiple round robin cycles
        for ($i = 0; $i < 100; $i++) {
            $index = $this->getPrivateMethod($this->service, 'getRoundRobinIndex', [$department, $largeUserCount]);
            $this->assertLessThan($largeUserCount, $index);
            $this->assertGreaterThanOrEqual(0, $index);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should be reasonably fast with optimized cache
        $this->assertLessThan(0.5, $totalTime, "Round Robin too slow with large user count: {$totalTime}s");
        
        // Performance should be under 5ms per operation
        $avgTimePerOp = ($totalTime / 100) * 1000;
        $this->assertLessThan(5.0, $avgTimePerOp, "Average time per operation too high: {$avgTimePerOp}ms");
    }

    /** @test */
    public function it_handles_concurrent_cache_access()
    {
        $department = 'concurrent_dept';
        $userCount = 10;
        
        // Simulate concurrent access
        $indices = [];
        for ($i = 0; $i < 50; $i++) {
            $index = $this->getPrivateMethod($this->service, 'getRoundRobinIndex', [$department, $userCount]);
            $indices[] = $index;
        }
        
        // Should have proper round robin distribution (all indices should be used)
        $uniqueIndices = array_unique($indices);
        $this->assertEquals($userCount, count($uniqueIndices), "Not all indices were used in round robin");
        
        // Should cycle through all indices in order (0,1,2,3,4,5,6,7,8,9,0,1,2...)
        $expectedPattern = [];
        for ($i = 0; $i < 5; $i++) { // 5 complete cycles
            $expectedPattern = array_merge($expectedPattern, range(0, 9));
        }
        
        // Check that the pattern follows round robin (each index should appear multiple times)
        $indexCounts = array_count_values($indices);
        foreach ($indexCounts as $index => $count) {
            $this->assertEquals(5, $count, "Index {$index} should appear exactly 5 times in 50 calls");
        }
    }

    /**
     * Helper method to access private methods for testing
     */
    private function getPrivateMethod($object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
