<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\Permission\DepartmentModulePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseApiController
{
    /**
     * Validate login identifier (username, email, or mobile) before password step.
     */
    public function validateUsername(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $login = $request->login;
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : (is_numeric($login) ? 'mobile' : 'username');

        $user = User::where($field, $login)->first();

        if (!$user) {
            return $this->errorResponse('Invalid username', 404);
        }

        if ($user->status !== 'active') {
            return $this->errorResponse('Account is inactive or suspended', 403);
        }

        return $this->successResponse([
            'login' => $login,
            'first_name' => $user->first_name,
        ], 'Username verified');
    }

    /**
     * Login user and get JWT token
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string', // can be username, email, or mobile
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $login = $request->login;
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : (is_numeric($login) ? 'mobile' : 'username');

        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        if ($user->status !== 'active') {
            return $this->errorResponse('Account is inactive or suspended', 403);
        }

        // Load user relationships
        $user->load(['teams', 'departments', 'roles']);

        $token = JWTAuth::fromUser($user);

        // Prepare user data with role and department info
        $userData = $user->makeHidden(['password']);
        
        // Add role information
        $userData->role_id = $user->roles->isNotEmpty() ? $user->roles->first()->id : null;
        $userData->role_name = $user->roles->isNotEmpty() ? $user->roles->first()->name : null;
        
        // Add department information
        $userData->department_id = $user->departments->isNotEmpty() ? $user->departments->first()->id : null;
        $userData->department_name = $user->departments->isNotEmpty() ? $user->departments->first()->name : null;
        
        // Add team information
        $userData->team_id = $user->teams->isNotEmpty() ? $user->teams->first()->id : null;
        $userData->team_name = $user->teams->isNotEmpty() ? $user->teams->first()->name : null;

        $permissionService = app(DepartmentModulePermissionService::class);
        $userData->module_permissions = $permissionService->modulePermissionsForUser($user->id);

        return $this->successResponse([
            'user' => $userData,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60
        ], 'Login successful');
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->successResponse(null, 'Successfully logged out');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to logout', 500);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh();
            return $this->successResponse([
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to refresh token', 401);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user();
        $user->load(['teams', 'departments', 'roles', 'creator:id,first_name,last_name']);

        $userData = $user->makeHidden(['password']);
        $permissionService = app(DepartmentModulePermissionService::class);
        $userData->module_permissions = $permissionService->modulePermissionsForUser($user->id);

        return $this->successResponse($userData, 'Profile retrieved successfully');
    }

    /**
     * Get authenticated user's department module permissions.
     */
    public function permissions(): JsonResponse
    {
        $user = auth()->user();
        $permissionService = app(DepartmentModulePermissionService::class);

        return $this->successResponse([
            'modules' => $permissionService->modulePermissionsForUser($user->id),
            'readable_modules' => $permissionService->readableModuleNamesForUser($user->id),
        ], 'Permissions retrieved successfully');
    }
}
