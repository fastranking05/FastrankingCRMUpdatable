<?php

namespace App\Http\Middleware;

use App\Services\Permission\DepartmentModulePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnyModuleReadMiddleware
{
    public function __construct(
        private readonly DepartmentModulePermissionService $permissions,
    ) {}

    /**
     * Allow access when the user belongs to at least one department with can_read on any module.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        if (!$this->permissions->userHasAnyReadPermission($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied. No module read access assigned to your department(s).',
            ], 403);
        }

        return $next($request);
    }
}
