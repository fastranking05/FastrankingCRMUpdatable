<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Quality;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QualityAssignmentService
{
    private const QUALITY_CONTROL_DEPT_NAME = 'Quality Control';
    private const DEFAULT_STATUS = 'QA-Pending';
    private const DEFAULT_AUDIT_STATUS = 'unqualified';

    /**
     * Automatically assign a Quality Control record to an appointment
     * Uses round-robin with workload balancing
     *
     * @param string $appointmentId
     * @return Quality|null
     */
    public function assignQualityControl(string $appointmentId): ?Quality
    {
        try {
            // Check if quality record already exists
            $existingQuality = Quality::where('appointment_id', $appointmentId)->first();
            if ($existingQuality) {
                Log::info("Quality record already exists for appointment: {$appointmentId}");
                return $existingQuality;
            }

            // Get next available Quality Control user using round-robin with workload
            $assignedUser = $this->getNextQualityControlUser();

            if (!$assignedUser) {
                Log::warning("No active Quality Control user found for assignment");
                return null;
            }

            // Create quality record
            $quality = Quality::create([
                'appointment_id' => $appointmentId,
                'auditstatus' => self::DEFAULT_AUDIT_STATUS,
                'status' => self::DEFAULT_STATUS,
                'assigned_user' => $assignedUser->id,
                'meeting_link' => null,
            ]);

            Log::info("Quality record created for appointment: {$appointmentId}, assigned to user: {$assignedUser->id}");

            return $quality;

        } catch (\Exception $e) {
            Log::error('Failed to assign Quality Control: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the next Quality Control user using round-robin with workload balancing
     * Prioritizes users with fewer active assignments
     *
     * @return User|null
     */
    private function getNextQualityControlUser(): ?User
    {
        // Get Quality Control department
        $department = Department::where('name', self::QUALITY_CONTROL_DEPT_NAME)
            ->where('status', 'active')
            ->first();

        if (!$department) {
            Log::warning("Quality Control department not found");
            return null;
        }

        // Get active users from Quality Control department
        $users = $department->users()
            ->where('status', 'active')
            ->pluck('users.id')
            ->toArray();

        if (empty($users)) {
            Log::warning("No active users found in Quality Control department");
            return null;
        }

        // Get workload count for each user (active quality assignments)
        $workloads = Quality::select('assigned_user', DB::raw('count(*) as count'))
            ->whereIn('assigned_user', $users)
            ->whereIn('status', ['QA-Pending', 'In Progress'])
            ->groupBy('assigned_user')
            ->pluck('count', 'assigned_user')
            ->toArray();

        // Get the last assigned user (for round-robin tracking)
        $lastAssignment = Quality::whereIn('assigned_user', $users)
            ->orderBy('created_at', 'desc')
            ->first();

        $lastAssignedUserId = $lastAssignment?->assigned_user;

        // Find the user with minimum workload
        // If tie, use round-robin from last assignment
        $minWorkload = PHP_INT_MAX;
        $candidates = [];

        foreach ($users as $userId) {
            $workload = $workloads[$userId] ?? 0;

            if ($workload < $minWorkload) {
                $minWorkload = $workload;
                $candidates = [$userId];
            } elseif ($workload === $minWorkload) {
                $candidates[] = $userId;
            }
        }

        // If multiple users have same workload, use round-robin
        if (count($candidates) > 1 && $lastAssignedUserId) {
            // Find the next user in round-robin from last assignment
            $lastIndex = array_search($lastAssignedUserId, $candidates);
            if ($lastIndex !== false) {
                $nextIndex = ($lastIndex + 1) % count($candidates);
                $selectedUserId = $candidates[$nextIndex];
            } else {
                // Last assigned user not in candidates, pick first
                $selectedUserId = $candidates[0];
            }
        } else {
            $selectedUserId = $candidates[0] ?? null;
        }

        return $selectedUserId ? User::find($selectedUserId) : null;
    }

    /**
     * Get workload statistics for Quality Control users
     *
     * @return array
     */
    public function getWorkloadStats(): array
    {
        $department = Department::where('name', self::QUALITY_CONTROL_DEPT_NAME)
            ->where('status', 'active')
            ->first();

        if (!$department) {
            return [];
        }

        $users = $department->users()
            ->where('status', 'active')
            ->withCount(['qualities as pending_count' => function ($query) {
                $query->whereIn('status', ['QA-Pending', 'In Progress']);
            }])
            ->get();

        return $users->map(function ($user) {
            return [
                'user_id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'pending_assignments' => $user->pending_count,
            ];
        })->toArray();
    }

    /**
     * Reassign a quality record to another user
     *
     * @param int $qualityId
     * @param int $newUserId
     * @return Quality|null
     */
    public function reassignQuality(int $qualityId, int $newUserId): ?Quality
    {
        try {
            $quality = Quality::find($qualityId);
            if (!$quality) {
                return null;
            }

            // Verify new user is from Quality Control
            $user = User::find($newUserId);
            if (!$user || !$this->isQualityControlUser($user)) {
                Log::warning("User {$newUserId} is not a Quality Control user");
                return null;
            }

            $quality->update(['assigned_user' => $newUserId]);

            Log::info("Quality {$qualityId} reassigned to user {$newUserId}");

            return $quality;

        } catch (\Exception $e) {
            Log::error('Failed to reassign Quality: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user belongs to Quality Control department
     *
     * @param User $user
     * @return bool
     */
    private function isQualityControlUser(User $user): bool
    {
        return $user->departments()
            ->where('name', self::QUALITY_CONTROL_DEPT_NAME)
            ->where('status', 'active')
            ->exists();
    }
}
