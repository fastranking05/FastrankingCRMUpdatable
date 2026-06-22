<?php

namespace App\Services\Permission;

use Illuminate\Support\Facades\DB;

class DepartmentModulePermissionService
{
    /**
     * Permission aliases that map to stored pivot columns.
     *
     * @var array<string, string>
     */
    private const PERMISSION_ALIASES = [
        'manage' => 'update',
    ];

    /**
     * Whether the user has the given module permission via any active department.
     */
    public function userHasPermission(int $userId, string $moduleName, string $permission): bool
    {
        $module = $this->findActiveModule($moduleName);

        if ($module === null) {
            return false;
        }

        $departmentIds = $this->activeDepartmentIdsForUser($userId);

        if ($departmentIds === []) {
            return false;
        }

        $permissionField = $this->permissionColumn($permission);

        if ($permissionField === null) {
            return false;
        }

        return DB::table('module_department')
            ->where('module_id', $module->id)
            ->whereIn('department_id', $departmentIds)
            ->where($permissionField, true)
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    public function readableModuleNamesForUser(int $userId): array
    {
        $departmentIds = $this->activeDepartmentIdsForUser($userId);

        if ($departmentIds === []) {
            return [];
        }

        return DB::table('module_department')
            ->join('modules', 'module_department.module_id', '=', 'modules.id')
            ->whereIn('module_department.department_id', $departmentIds)
            ->where('modules.status', 'active')
            ->where('module_department.can_read', true)
            ->distinct()
            ->orderBy('modules.name')
            ->pluck('modules.name')
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{module: string, can_create: bool, can_read: bool, can_update: bool, can_delete: bool}>
     */
    public function modulePermissionsForUser(int $userId): array
    {
        $departmentIds = $this->activeDepartmentIdsForUser($userId);

        if ($departmentIds === []) {
            return [];
        }

        $rows = DB::table('module_department')
            ->join('modules', 'module_department.module_id', '=', 'modules.id')
            ->whereIn('module_department.department_id', $departmentIds)
            ->where('modules.status', 'active')
            ->select(
                'modules.name as module',
                'module_department.can_create',
                'module_department.can_read',
                'module_department.can_update',
                'module_department.can_delete',
            )
            ->get();

        $merged = [];

        foreach ($rows as $row) {
            $name = (string) $row->module;

            if (!isset($merged[$name])) {
                $merged[$name] = [
                    'module' => $name,
                    'can_create' => false,
                    'can_read' => false,
                    'can_update' => false,
                    'can_delete' => false,
                ];
            }

            foreach (['can_create', 'can_read', 'can_update', 'can_delete'] as $flag) {
                if ((bool) $row->$flag) {
                    $merged[$name][$flag] = true;
                }
            }
        }

        ksort($merged);

        return array_values($merged);
    }

    public function userHasAnyReadPermission(int $userId): bool
    {
        return $this->readableModuleNamesForUser($userId) !== [];
    }

    /**
     * @param  array<int, string>  $requestedTypes
     * @return array<int, string>
     */
    public function allowedSearchEntityTypesForUser(int $userId, array $requestedTypes = []): array
    {
        $readableModules = $this->readableModuleNamesForUser($userId);
        $readableLookup = array_fill_keys($readableModules, true);

        $allowed = [];

        foreach (config('global_search.entity_module_map', []) as $entityType => $moduleNames) {
            foreach ($moduleNames as $moduleName) {
                if (isset($readableLookup[$moduleName])) {
                    $allowed[] = $entityType;
                    break;
                }
            }
        }

        $allowed = array_values(array_unique($allowed));

        if ($requestedTypes === []) {
            return $allowed;
        }

        return array_values(array_intersect($allowed, $requestedTypes));
    }

    /**
     * @return array<int, int>
     */
    public function activeDepartmentIdsForUser(int $userId): array
    {
        return DB::table('department_user')
            ->join('departments', 'department_user.department_id', '=', 'departments.id')
            ->where('department_user.user_id', $userId)
            ->where('departments.status', 'active')
            ->pluck('departments.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function findActiveModule(string $moduleName): ?object
    {
        return DB::table('modules')
            ->where('name', $moduleName)
            ->where('status', 'active')
            ->first();
    }

    private function permissionColumn(string $permission): ?string
    {
        $normalized = self::PERMISSION_ALIASES[strtolower($permission)] ?? strtolower($permission);

        return match ($normalized) {
            'create', 'read', 'update', 'delete' => 'can_' . $normalized,
            default => null,
        };
    }
}
