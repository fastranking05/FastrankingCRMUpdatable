<?php

namespace App\Services\AI;

use App\Models\User;
use App\Services\AI\Data\UserDataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserDataScopeService
{
    public function resolve(User $user): UserDataScope
    {
        $user->loadMissing(['roles', 'teams', 'departments']);

        $accessLevel = $this->determineAccessLevel($user);
        $allowedUserIds = $accessLevel === 'admin'
            ? []
            : ($accessLevel === 'manager'
                ? array_values(array_unique(array_merge($this->getTeamMemberIds($user), [$user->id])))
                : [$user->id]);

        return new UserDataScope(
            accessLevel: $accessLevel,
            allowedUserIds: $allowedUserIds,
            roleNames: $user->roles->pluck('name')->toArray(),
            teamNames: $user->teams->pluck('name')->toArray(),
            departmentNames: $user->departments->pluck('name')->toArray(),
        );
    }

    public function scopeQuery(Builder $query, User $user, string $column = 'created_by'): Builder
    {
        $scope = $this->resolve($user);

        if ($scope->isAdmin()) {
            return $query;
        }

        return $query->whereIn($column, $scope->allowedUserIds);
    }

    public function canAccessRecord(User $user, int $recordCreatedBy): bool
    {
        $scope = $this->resolve($user);

        if ($scope->isAdmin()) {
            return true;
        }

        return in_array($recordCreatedBy, $scope->allowedUserIds, true);
    }

    public function hasModulePermission(User $user, string $moduleName, string $permission): bool
    {
        $module = DB::table('modules')
            ->where('name', $moduleName)
            ->where('status', 'active')
            ->first();

        if (!$module) {
            return false;
        }

        $userRoleIds = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $user->id)
            ->where('roles.status', 'active')
            ->pluck('roles.id')
            ->toArray();

        if ($userRoleIds === []) {
            return false;
        }

        $permissionField = 'can_' . $permission;

        return DB::table('module_role')
            ->where('module_id', $module->id)
            ->whereIn('role_id', $userRoleIds)
            ->where($permissionField, true)
            ->exists();
    }

    private function determineAccessLevel(User $user): string
    {
        $isAdmin = $user->roles->contains(fn ($role) => in_array(strtolower($role->name), ['admin', 'superadmin', 'super_admin'], true));

        if ($isAdmin || strtolower($user->user_type) === 'admin') {
            return 'admin';
        }

        $isLeadGen = $user->departments->contains(fn ($dept) => in_array(strtolower($dept->name), ['lead generation', 'lead_generation', 'leadgeneration'], true));
        $isManager = $user->roles->contains(fn ($role) => in_array(strtolower($role->name), ['manager', 'team manager', 'team_manager'], true));
        $hasTeams = $user->teams->isNotEmpty();

        if ($isManager && $isLeadGen && $hasTeams) {
            return 'manager';
        }

        if ($hasTeams) {
            return 'manager';
        }

        return 'executive';
    }

    /**
     * @return array<int, int>
     */
    private function getTeamMemberIds(User $user): array
    {
        $teamIds = $user->teams->pluck('id')->toArray();

        if ($teamIds === []) {
            return [];
        }

        return DB::table('team_user')
            ->whereIn('team_id', $teamIds)
            ->where('user_id', '!=', $user->id)
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }
}
