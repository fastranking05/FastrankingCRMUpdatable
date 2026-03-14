<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleName, string $permission): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // All users must have explicit permissions - no admin bypass
        // if ($user->user_type === 'admin') {
        //     return $next($request);
        // }

        // Check if user has required permission for the module - ALWAYS fresh from DB
        $hasPermission = $this->checkPermissionFresh($user->id, $moduleName, $permission);

        if (!$hasPermission) {
            Log::warning('Permission denied', [
                'user_id' => $user->id,
                'module' => $moduleName,
                'permission' => $permission,
                'url' => $request->url(),
                'method' => $request->method(),
                'timestamp' => now()->toDateTimeString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Permission denied. You do not have ' . $permission . ' access for ' . $moduleName . '. Contact administrator.'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check permission with fresh database query (no caching)
     * This ensures real-time permission updates
     */
    private function checkPermissionFresh(int $userId, string $moduleName, string $permission): bool
    {
        // Use DB facade for direct query without any Eloquent caching
        $module = DB::table('modules')
            ->where('name', $moduleName)
            ->where('status', 'active')
            ->first();

        if (!$module) {
            Log::debug('Module not found or inactive', ['module' => $moduleName]);
            return false;
        }

        // Get active role IDs for the user using fresh query
        $userRoleIds = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $userId)
            ->where('roles.status', 'active')
            ->pluck('roles.id')
            ->toArray();

        if (empty($userRoleIds)) {
            Log::debug('User has no active roles', ['user_id' => $userId]);
            return false;
        }

        // Check permission in module_role pivot table with fresh query
        $permissionField = 'can_' . $permission;

        $hasPermission = DB::table('module_role')
            ->where('module_id', $module->id)
            ->whereIn('role_id', $userRoleIds)
            ->where($permissionField, true)
            ->exists();

        Log::debug('Permission check result', [
            'user_id' => $userId,
            'module' => $moduleName,
            'permission' => $permission,
            'has_permission' => $hasPermission,
            'user_roles' => $userRoleIds,
            'module_id' => $module->id
        ]);

        return $hasPermission;
    }
}
