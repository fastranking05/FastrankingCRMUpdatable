<?php

namespace Tests\Concerns;

use App\Models\Department;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Str;

trait SetsUpUserWithModuleReadPermissions
{
    protected User $authenticatedUser;

    /**
     * Bypass JWT/permission middleware, create a user and grant can_read on named modules via department.
     */
    protected function actingAsUserWithModuleRead(array $moduleNames = []): User
    {
        $suffix = Str::replace('-', '', (string) Str::uuid());
        $uniqueShort = substr($suffix, 0, 15);

        $this->authenticatedUser = User::create([
            'first_name' => 'Cursor',
            'middle_name' => null,
            'last_name' => 'Test',
            'gender' => 'other',
            'dob' => '1995-01-15',
            'email' => 'ct_'.$uniqueShort.'@example.test',
            'mobile' => '+99'.$uniqueShort,
            'username' => 'ct_'.$uniqueShort,
            'password' => bcrypt('password'),
            'date_of_joining' => '2024-01-01',
            'emp_id' => 'EMP'.$uniqueShort,
            'status' => 'active',
            'user_type' => 'admin',
            'designation' => 'QA',
            'created_by' => null,
        ]);

        $department = Department::create([
            'name' => 'Test Department '.$uniqueShort,
            'description' => null,
            'status' => 'active',
            'created_by' => $this->authenticatedUser->id,
        ]);

        $this->authenticatedUser->departments()->sync([$department->id]);

        foreach ($moduleNames as $moduleName) {
            $module = Module::firstOrCreate(
                ['name' => $moduleName],
                [
                    'description' => 'Test module',
                    'status' => 'active',
                    'created_by' => $this->authenticatedUser->id,
                ]
            );

            $department->modules()->syncWithoutDetaching([
                $module->id => [
                    'can_create' => false,
                    'can_read' => true,
                    'can_update' => false,
                    'can_delete' => false,
                ],
            ]);
        }

        $this->actingAs($this->authenticatedUser);

        return $this->authenticatedUser;
    }

    /**
     * Assert Laravel cursor paginator shape (from JsonSerializable / toArray).
     */
    protected function assertCursorPaginatorShape(array $paginator): void
    {
        $this->assertArrayHasKey('data', $paginator);
        $this->assertArrayHasKey('path', $paginator);
        $this->assertArrayHasKey('per_page', $paginator);
        $this->assertArrayHasKey('next_page_url', $paginator);
        $this->assertArrayHasKey('prev_page_url', $paginator);
        $this->assertArrayHasKey('next_cursor', $paginator);
        $this->assertArrayHasKey('prev_cursor', $paginator);
        $this->assertIsArray($paginator['data']);
    }
}
