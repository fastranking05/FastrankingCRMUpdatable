<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DealsModuleSeeder extends Seeder
{
    /**
     * Register the Deals module and grant permissions to roles that had
     * Consultation or legacy Opportunity access.
     */
    public function run(): void
    {
        $dealsModule = Module::firstOrCreate(
            ['name' => 'Deals'],
            [
                'description' => 'Sales deals pipeline with stages, values, and business comments',
                'status' => 'active',
                'created_by' => 1,
            ]
        );

        $sourceModule = Module::where('name', 'Opportunity')->first()
            ?? Module::where('name', 'Consultation')->first();

        if ($sourceModule) {
            $rolesWithAccess = Role::whereHas('modules', function ($query) use ($sourceModule) {
                $query->where('modules.id', $sourceModule->id)
                    ->where('module_role.can_read', true);
            })->get();

            foreach ($rolesWithAccess as $role) {
                $sourcePermissions = $role->modules()
                    ->where('modules.id', $sourceModule->id)
                    ->first()
                    ?->pivot;

                $role->modules()->syncWithoutDetaching([
                    $dealsModule->id => [
                        'can_create' => (bool) ($sourcePermissions->can_create ?? false),
                        'can_read' => (bool) ($sourcePermissions->can_read ?? true),
                        'can_update' => (bool) ($sourcePermissions->can_update ?? false),
                        'can_delete' => (bool) ($sourcePermissions->can_delete ?? false),
                    ],
                ]);
            }
        } else {
            $fallbackRole = Role::where('status', 'active')->first();
            if ($fallbackRole) {
                $fallbackRole->modules()->syncWithoutDetaching([
                    $dealsModule->id => [
                        'can_create' => false,
                        'can_read' => true,
                        'can_update' => false,
                        'can_delete' => false,
                    ],
                ]);
            }
        }

        Module::where('name', 'Opportunity')->update(['status' => 'inactive']);

        $this->command?->info('Deals module registered and permissions synced.');
    }
}
