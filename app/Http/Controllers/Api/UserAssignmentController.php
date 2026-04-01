<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\UserAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAssignmentController extends BaseApiController
{
    private UserAssignmentService $userAssignmentService;

    public function __construct(UserAssignmentService $userAssignmentService)
    {
        $this->userAssignmentService = $userAssignmentService;
    }

    /**
     * Get Sales department assignment statistics
     */
    public function getSalesAssignmentStats(): JsonResponse
    {
        try {
            $stats = $this->userAssignmentService->getSalesAssignmentStats();
            
            return $this->successResponse($stats, 'Sales assignment statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get assignment statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get next user to be assigned (for preview)
     */
    public function getNextAssignedUser(): JsonResponse
    {
        try {
            $nextUser = $this->userAssignmentService->getNextAssignedUser();
            
            if (!$nextUser) {
                return $this->errorResponse('No active Sales users available', 404);
            }

            $userData = [
                'id' => $nextUser->id,
                'name' => $nextUser->first_name . ' ' . $nextUser->last_name,
                'email' => $nextUser->email,
            ];

            return $this->successResponse($userData, 'Next assigned user retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get next assigned user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset round robin index for Sales department
     */
    public function resetRoundRobinIndex(): JsonResponse
    {
        try {
            $this->userAssignmentService->resetRoundRobinIndex('sales');
            
            return $this->successResponse(null, 'Round Robin index reset successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset Round Robin index: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reassign consultations for inactive user
     */
    public function reassignConsultationsForInactiveUser(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $success = $this->userAssignmentService->reassignConsultationsForInactiveUser($request->user_id);
            
            if ($success) {
                return $this->successResponse(null, 'Consultations reassigned successfully');
            } else {
                return $this->errorResponse('Failed to reassign consultations', 500);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Error reassigning consultations: ' . $e->getMessage(), 500);
        }
    }
}
