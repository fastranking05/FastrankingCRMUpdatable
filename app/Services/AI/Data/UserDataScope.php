<?php

namespace App\Services\AI\Data;

readonly class UserDataScope
{
    /**
     * @param  array<int, int>  $allowedUserIds
     * @param  array<int, string>  $roleNames
     * @param  array<int, string>  $teamNames
     * @param  array<int, string>  $departmentNames
     */
    public function __construct(
        public string $accessLevel,
        public array $allowedUserIds,
        public array $roleNames,
        public array $teamNames,
        public array $departmentNames,
    ) {}

    public function isAdmin(): bool
    {
        return $this->accessLevel === 'admin';
    }

    public function toArray(): array
    {
        return [
            'access_level' => $this->accessLevel,
            'allowed_user_ids' => $this->allowedUserIds,
            'role_names' => $this->roleNames,
            'team_names' => $this->teamNames,
            'department_names' => $this->departmentNames,
        ];
    }
}
