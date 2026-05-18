<?php

namespace App\Services;

use App\Models\Department;
use App\Models\SeoDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SeoAssignmentService
{
    private const DIGITAL_MARKETING_DEPT_NAME = 'Digital Marketing';
    private const DEFAULT_STATUS = 'Pending';

    /**
     * Automatically assign an SEO record to an appointment
     * Uses round-robin with workload balancing
     *
     * @param string $appointmentId
     * @param int $followupBusinessId
     * @return SeoDetail|null
     */
    public function assignSeo(string $appointmentId, int $followupBusinessId): ?SeoDetail
    {
        try {
            // Check if SEO record already exists for this business
            $existingSeoDetail = SeoDetail::where('followup_business_id', $followupBusinessId)->first();
            if ($existingSeoDetail) {
                Log::info("SEO record already exists for business: {$followupBusinessId}");
                return $existingSeoDetail;
            }

            // Get next available Digital Marketing user using round-robin with workload
            $assignedUser = $this->getNextDigitalMarketingUser();

            if (!$assignedUser) {
                Log::warning("No active Digital Marketing user found for assignment");
                return null;
            }

            // Create SEO detail record
            $seoDetail = SeoDetail::create([
                'followup_business_id' => $followupBusinessId,
                'status' => self::DEFAULT_STATUS,
                'assigned_user' => $assignedUser->id,
            ]);

            Log::info("SEO record created for appointment: {$appointmentId}, business: {$followupBusinessId}, assigned to user: {$assignedUser->id}");

            return $seoDetail;

        } catch (\Exception $e) {
            Log::error('Failed to assign SEO: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get next Digital Marketing user using round-robin with workload balancing
     * Prioritizes users with fewer active assignments
     *
     * @return User|null
     */
    private function getNextDigitalMarketingUser(): ?User
    {
        // Get Digital Marketing department
        $department = Department::where('name', self::DIGITAL_MARKETING_DEPT_NAME)
            ->where('status', 'active')
            ->first();

        if (!$department) {
            Log::warning("Digital Marketing department not found");
            return null;
        }

        // Get active users from Digital Marketing department
        $users = $department->users()
            ->where('status', 'active')
            ->pluck('users.id')
            ->toArray();

        if (empty($users)) {
            Log::warning("No active users found in Digital Marketing department");
            return null;
        }

        // Get workload count for each user (active SEO assignments)
        $workloads = SeoDetail::select('assigned_user', DB::raw('count(*) as count'))
            ->whereIn('assigned_user', $users)
            ->whereIn('status', ['Pending', 'In Progress'])
            ->groupBy('assigned_user')
            ->pluck('count', 'assigned_user')
            ->toArray();

        // Get last assigned user (for round-robin tracking)
        $lastAssignment = SeoDetail::whereIn('assigned_user', $users)
            ->orderBy('created_at', 'desc')
            ->first();

        $lastAssignedUserId = $lastAssignment?->assigned_user;

        // Find user with minimum workload
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
            // Find next user in round-robin from last assignment
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
     * Get workload statistics for Digital Marketing users
     *
     * @return array
     */
    public function getWorkloadStats(): array
    {
        $department = Department::where('name', self::DIGITAL_MARKETING_DEPT_NAME)
            ->where('status', 'active')
            ->first();

        if (!$department) {
            return [];
        }

        $users = $department->users()
            ->where('status', 'active')
            ->withCount(['assignedSeoDetails as pending_count' => function ($query) {
                $query->whereIn('status', ['Pending', 'In Progress']);
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
     * Reassign an SEO record to another user
     *
     * @param int $seoDetailId
     * @param int $newUserId
     * @return SeoDetail|null
     */
    public function reassignSeo(int $seoDetailId, int $newUserId): ?SeoDetail
    {
        try {
            $seoDetail = SeoDetail::find($seoDetailId);
            if (!$seoDetail) {
                return null;
            }

            // Verify new user is from Digital Marketing
            $user = User::find($newUserId);
            if (!$user || !$this->isDigitalMarketingUser($user)) {
                Log::warning("User {$newUserId} is not a Digital Marketing user");
                return null;
            }

            $seoDetail->update(['assigned_user' => $newUserId]);

            Log::info("SEO {$seoDetailId} reassigned to user {$newUserId}");

            return $seoDetail;

        } catch (\Exception $e) {
            Log::error('Failed to reassign SEO: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user belongs to Digital Marketing department
     *
     * @param User $user
     * @return bool
     */
    private function isDigitalMarketingUser(User $user): bool
    {
        return $user->departments()
            ->where('name', self::DIGITAL_MARKETING_DEPT_NAME)
            ->where('status', 'active')
            ->exists();
    }
}
