<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public - No JWT Required)
|--------------------------------------------------------------------------
*/

Route::post('/validate-username', [AuthController::class, 'validateUsername'])->name('auth.validate-username');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// Protected auth routes (require JWT)
Route::middleware(['jwt.auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::get('/profile', [AuthController::class, 'profile'])->name('auth.profile');
    Route::get('/permissions', [AuthController::class, 'permissions'])->name('auth.permissions');
    
    // Debug endpoint to check current user permissions
    Route::get('/debug/permissions', function () {
        $user = auth()->user();
        $modules = \Illuminate\Support\Facades\DB::table('modules')->get();
        $userDepartments = \Illuminate\Support\Facades\DB::table('department_user')
            ->where('user_id', $user->id)
            ->pluck('department_id')
            ->toArray();
        
        $permissions = [];
        if (!empty($userDepartments)) {
            $permissions = \Illuminate\Support\Facades\DB::table('module_department')
                ->whereIn('department_id', $userDepartments)
                ->join('modules', 'module_department.module_id', '=', 'modules.id')
                ->select('modules.name as module_name', 'module_department.*')
                ->get();
        }
        
        return response()->json([
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'is_admin_bypass' => $user->user_type === 'admin',
            'user_departments' => $userDepartments,
            'all_modules' => $modules,
            'department_permissions' => $permissions
        ]);
    });
});
