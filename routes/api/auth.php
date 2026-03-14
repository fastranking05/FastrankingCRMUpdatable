<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public - No JWT Required)
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// Protected auth routes (require JWT)
Route::middleware(['jwt.auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::get('/profile', [AuthController::class, 'profile'])->name('auth.profile');
    
    // Debug endpoint to check current user permissions
    Route::get('/debug/permissions', function () {
        $user = auth()->user();
        $modules = \Illuminate\Support\Facades\DB::table('modules')->get();
        $userRoles = \Illuminate\Support\Facades\DB::table('role_user')
            ->where('user_id', $user->id)
            ->pluck('role_id')
            ->toArray();
        
        $permissions = [];
        if (!empty($userRoles)) {
            $permissions = \Illuminate\Support\Facades\DB::table('module_role')
                ->whereIn('role_id', $userRoles)
                ->join('modules', 'module_role.module_id', '=', 'modules.id')
                ->select('modules.name as module_name', 'module_role.*')
                ->get();
        }
        
        return response()->json([
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'is_admin_bypass' => $user->user_type === 'admin',
            'user_roles' => $userRoles,
            'all_modules' => $modules,
            'role_permissions' => $permissions
        ]);
    });
});
