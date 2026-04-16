<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserAssignmentService
{
    private const CACHE_PREFIX = 'user_assignment_';
    private const CACHE_TTL = 3600; // 1 hour
    
    /**
     * Create and assign consultation to Sales department user using Round Robin with load balancing
     */
    public function createAndAssignConsultation(string $appointmentId): ?array
    {
        try {
            $salesDepartment = $this->getSalesDepartment();
            if (!$salesDepartment) {
                Log::error('Sales department not found');
                return null;
            }

            $activeSalesUsers = $this->getActiveSalesUsers($salesDepartment->id);
            
            if ($activeSalesUsers->isEmpty()) {
                Log::error('No active users found in Sales department');
                return null;
            }

            $assignedUser = $this->selectUserByRoundRobin($activeSalesUsers);
            
            Log::info('UserAssignmentService: selectUserByRoundRobin result', [
                'assigned_user_is_null' => is_null($assignedUser),
                'assigned_user_id' => $assignedUser ? $assignedUser->id : null,
            ]);
            
            if ($assignedUser) {
                // Get appointment details
                $appointment = Appointment::find($appointmentId);

                // Create consultation with assigned user
                $consultation = Consultation::create([
                    'appointment_id' => $appointmentId,
                    'status' => 'scheduled',
                    'assigned_user' => $assignedUser->id,
                    'meeting_date' => $appointment ? $appointment->date : null,
                    'meeting_slot' => $appointment ? $appointment->time_slot_id : null,
                ]);

                $this->updateUserLoadCounter($assignedUser->id);
                
                Log::info("Consultation {$consultation->id} created and assigned to user {$assignedUser->id}");
                
                return [
                    'consultation' => $consultation,
                    'assigned_user' => $assignedUser,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating and assigning consultation: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Sales department
     */
    private function getSalesDepartment(): ?Department
    {
        Log::info('UserAssignmentService: Looking for Sales department');
        
        $department = Department::where('name', 'Sales')
            ->where('status', 'active')
            ->first();
            
        if ($department) {
            Log::info('UserAssignmentService: Sales department found', [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'department_status' => $department->status,
            ]);
        } else {
            Log::error('UserAssignmentService: Sales department not found', [
                'search_name' => 'Sales',
                'search_status' => 'active',
            ]);
            
            // Log all departments for debugging
            $allDepartments = Department::all(['id', 'name', 'status']);
            Log::info('UserAssignmentService: All available departments', [
                'departments' => $allDepartments->toArray(),
            ]);
        }
        
        return $department;
    }

    /**
     * Get active users from Sales department
     */
    private function getActiveSalesUsers(int $departmentId)
    {
        Log::info('UserAssignmentService: Getting active users for department', [
            'department_id' => $departmentId,
        ]);
        
        // First, let's check if there are any users with this department at all
        $anyUsersWithDept = User::whereHas('departments', function ($query) use ($departmentId) {
            $query->where('departments.id', $departmentId);
        })->get();
        
        Log::info('UserAssignmentService: Users with department (any status)', [
            'department_id' => $departmentId,
            'count' => $anyUsersWithDept->count(),
            'users' => $anyUsersWithDept->pluck('id', 'first_name')->toArray(),
        ]);
        
        // Now check for active users with active department
        $users = User::whereHas('departments', function ($query) use ($departmentId) {
            $query->where('departments.id', $departmentId)
                  ->where('departments.status', 'active');
        })
        ->where('status', 'active')
        ->select(['id', 'first_name', 'last_name', 'email'])
        ->get();
        
        Log::info('UserAssignmentService: Active users with active department', [
            'department_id' => $departmentId,
            'count' => $users->count(),
            'users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                ];
            })->toArray(),
        ]);
        
        // If no users found, let's debug further
        if ($users->isEmpty()) {
            // Check all active users
            $allActiveUsers = User::where('status', 'active')->get();
            Log::info('UserAssignmentService: All active users in system', [
                'count' => $allActiveUsers->count(),
                'users' => $allActiveUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'status' => $user->status,
                    ];
                })->toArray(),
            ]);
            
            // Check department-user relationships for active users
            $activeUsersWithDepts = User::where('status', 'active')
                ->with('departments')
                ->get();
                
            Log::info('UserAssignmentService: Active users with their departments', [
                'users_departments' => $activeUsersWithDepts->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'departments' => $user->departments->map(function($dept) {
                            return [
                                'id' => $dept->id,
                                'name' => $dept->name,
                                'status' => $dept->status,
                            ];
                        }),
                    ];
                })->toArray(),
            ]);
        }
        
        return $users;
    }

    /**
     * Select user using Round Robin with load balancing
     */
    private function selectUserByRoundRobin($users): ?User
    {
        // IMMEDIATE ENTRY LOG - This should always appear
        Log::emergency('UserAssignmentService: ENTER selectUserByRoundRobin', [
            'users_count' => $users->count(),
            'users_empty' => $users->isEmpty(),
        ]);

        if ($users->isEmpty()) {
            Log::error('UserAssignmentService: selectUserByRoundRobin received empty users collection');
            return null;
        }

        try {
            // Get current load for each user
            $userIds = $users->pluck('id')->toArray();
            Log::info('UserAssignmentService: Getting user loads', [
                'user_ids' => $userIds,
            ]);
            
            $userLoads = $this->getUserLoads($userIds);
            
            Log::info('UserAssignmentService: User loads retrieved', [
                'user_loads' => $userLoads,
            ]);
            
            // Sort users by current load (ascending) for load balancing
            $sortedUsers = $users->sortBy(function ($user) use ($userLoads) {
                return $userLoads[$user->id] ?? 0;
            });

            Log::info('UserAssignmentService: Users sorted by load', [
                'sorted_users' => $sortedUsers->map(function($user) use ($userLoads) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'load' => $userLoads[$user->id] ?? 0,
                    ];
                })->toArray(),
            ]);

            // Get the next user in round robin order from the least loaded users
            $leastLoad = $userLoads[$sortedUsers->first()->id] ?? 0;
            $leastLoadedUsers = $sortedUsers->filter(function ($user) use ($userLoads, $leastLoad) {
                return ($userLoads[$user->id] ?? 0) === $leastLoad;
            });

            Log::info('UserAssignmentService: Least loaded users identified', [
                'least_load' => $leastLoad,
                'least_loaded_users_count' => $leastLoadedUsers->count(),
                'least_loaded_users' => $leastLoadedUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                    ];
                })->toArray(),
            ]);

            // Use round robin to select from the least loaded users
            $roundRobinIndex = $this->getRoundRobinIndex('sales', $leastLoadedUsers->count());
            
            Log::info('UserAssignmentService: Round robin index calculated', [
                'department' => 'sales',
                'user_count' => $leastLoadedUsers->count(),
                'round_robin_index' => $roundRobinIndex,
            ]);
            
            // CRITICAL: Check if index is valid
            if ($roundRobinIndex < 0 || $roundRobinIndex >= $leastLoadedUsers->count()) {
                Log::error('UserAssignmentService: Invalid round robin index', [
                    'round_robin_index' => $roundRobinIndex,
                    'user_count' => $leastLoadedUsers->count(),
                ]);
                return null;
            }
            
            $selectedUser = $leastLoadedUsers->values()->get($roundRobinIndex);

            if ($selectedUser) {
                Log::info('UserAssignmentService: User selected successfully', [
                    'selected_user_id' => $selectedUser->id,
                    'selected_user_name' => $selectedUser->first_name . ' ' . $selectedUser->last_name,
                ]);
            } else {
                Log::error('UserAssignmentService: Failed to select user - get() returned null', [
                    'round_robin_index' => $roundRobinIndex,
                    'least_loaded_users_count' => $leastLoadedUsers->count(),
                    'least_loaded_users_values' => $leastLoadedUsers->values()->toArray(),
                ]);
            }

            return $selectedUser;
        } catch (\Exception $e) {
            Log::error('UserAssignmentService: EXCEPTION in selectUserByRoundRobin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    /**
     * Get current consultation load for each user
     */
    private function getUserLoads($userIds): array
    {
        $loads = Consultation::whereIn('assigned_user', $userIds)
            ->whereNotIn('status', ['closed', 'completed', 'cancelled'])
            ->selectRaw('assigned_user, COUNT(*) as `load`')
            ->groupBy('assigned_user')
            ->pluck('load', 'assigned_user')
            ->toArray();

        // Ensure all users have a load count (default 0)
        foreach ($userIds as $userId) {
            if (!isset($loads[$userId])) {
                $loads[$userId] = 0;
            }
        }

        return $loads;
    }

    /**
     * Get round robin index for department
     */
    private function getRoundRobinIndex(string $department, int $userCount): int
    {
        $cacheKey = self::CACHE_PREFIX . $department . '_index';
        
        // Use atomic increment for better performance
        $currentIndex = Cache::increment($cacheKey, 1);
        
        // Set expiration on first access or if cache doesn't exist
        if ($currentIndex === 1 || !Cache::has($cacheKey)) {
            Cache::put($cacheKey, $currentIndex, self::CACHE_TTL);
        }
        
        // Use modulo to get index within range
        // Ensure we never return negative index
        $index = ($currentIndex - 1) % $userCount;
        
        // Handle negative modulo result (PHP can return negative for negative dividends)
        if ($index < 0) {
            $index += $userCount;
        }
        
        Log::info('UserAssignmentService: getRoundRobinIndex calculated', [
            'department' => $department,
            'user_count' => $userCount,
            'current_index' => $currentIndex,
            'calculated_index' => $index,
        ]);
        
        return $index;
    }

    /**
     * Assign consultation to user (legacy method - kept for compatibility)
     */
    private function assignConsultation(Consultation $consultation, User $user): void
    {
        $consultation->update([
            'assigned_user' => $user->id,
            'status' => 'scheduled',
        ]);
    }

    /**
     * Update user assignment counter for statistics
     */
    private function updateUserLoadCounter(int $userId): void
    {
        $cacheKey = self::CACHE_PREFIX . 'load_' . $userId;
        $currentLoad = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $currentLoad + 1, self::CACHE_TTL);
    }

    /**
     * Reset round robin index for a department
     */
    public function resetRoundRobinIndex(string $department): void
    {
        $cacheKey = self::CACHE_PREFIX . $department . '_index';
        Cache::forget($cacheKey);
    }

    /**
     * Get current assignment statistics for Sales department
     */
    public function getSalesAssignmentStats(): array
    {
        $salesDepartment = $this->getSalesDepartment();
        if (!$salesDepartment) {
            return [];
        }

        $users = $this->getActiveSalesUsers($salesDepartment->id);
        $userIds = $users->pluck('id');
        
        $consultationLoads = $this->getUserLoads($userIds);
        
        $stats = [];
        foreach ($users as $user) {
            $stats[] = [
                'user_id' => $user->id,
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'current_load' => $consultationLoads[$user->id] ?? 0,
                'email' => $user->email,
            ];
        }

        // Sort by current load
        usort($stats, function ($a, $b) {
            return $a['current_load'] - $b['current_load'];
        });

        return $stats;
    }

    /**
     * Reassign consultations if a user becomes inactive
     */
    public function reassignConsultationsForInactiveUser(int $userId): bool
    {
        try {
            $consultations = Consultation::where('assigned_user', $userId)
                ->whereNotIn('status', ['closed', 'completed', 'cancelled'])
                ->get();

            foreach ($consultations as $consultation) {
                $newUser = $this->assignConsultationToSalesUser($consultation);
                if (!$newUser) {
                    Log::error("Failed to reassign consultation {$consultation->id}");
                    return false;
                }
            }

            // Clear user load cache
            $cacheKey = self::CACHE_PREFIX . 'load_' . $userId;
            Cache::forget($cacheKey);

            Log::info("Reassigned consultations for inactive user {$userId}");
            return true;
        } catch (\Exception $e) {
            Log::error('Error reassigning consultations: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get next user to be assigned (for testing/preview)
     */
    public function getNextAssignedUser(): ?User
    {
        $salesDepartment = $this->getSalesDepartment();
        if (!$salesDepartment) {
            return null;
        }

        $activeSalesUsers = $this->getActiveSalesUsers($salesDepartment->id);
        if ($activeSalesUsers->isEmpty()) {
            return null;
        }

        return $this->selectUserByRoundRobin($activeSalesUsers);
    }
}
