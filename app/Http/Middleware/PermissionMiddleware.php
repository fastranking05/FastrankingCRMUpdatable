<?php

namespace App\Http\Middleware;

use App\Services\Permission\DepartmentModulePermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function __construct(
        private readonly DepartmentModulePermissionService $permissions,
    ) {}

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
                'message' => 'Unauthorized',
            ], 401);
        }

        $hasPermission = $this->permissions->userHasPermission($user->id, $moduleName, $permission);

        if (!$hasPermission) {
            Log::warning('Permission denied', [
                'user_id' => $user->id,
                'module' => $moduleName,
                'permission' => $permission,
                'url' => $request->url(),
                'method' => $request->method(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Permission denied. You do not have ' . $permission . ' access for ' . $moduleName . '. Contact administrator.',
            ], 403);
        }

        return $next($request);
    }
}
