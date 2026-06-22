<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('module_role') && ! Schema::hasTable('module_department')) {
            Schema::rename('module_role', 'module_department');
        }

        if (! Schema::hasTable('module_department')) {
            Schema::create('module_department', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('module_id');
                $table->boolean('can_create')->default(false);
                $table->boolean('can_read')->default(false);
                $table->boolean('can_update')->default(false);
                $table->boolean('can_delete')->default(false);
                $table->timestamps();

                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
                $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
                $table->unique(['department_id', 'module_id']);
            });

            return;
        }

        if (! Schema::hasColumn('module_department', 'role_id')) {
            if (! Schema::hasColumn('module_department', 'department_id')) {
                Schema::table('module_department', function (Blueprint $table) {
                    $table->unsignedBigInteger('department_id')->after('id');
                    $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
                    $table->unique(['department_id', 'module_id']);
                });
            }

            return;
        }

        $this->dropForeignKeysOn('module_department');
        $this->dropUniqueIndexesContaining('module_department', ['role_id', 'module_id']);

        Schema::table('module_department', function (Blueprint $table) {
            if (! Schema::hasColumn('module_department', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('id');
            }
        });

        foreach (DB::table('module_department')->orderBy('id')->get() as $row) {
            $role = DB::table('roles')->where('id', $row->role_id)->first();

            if ($role === null) {
                DB::table('module_department')->where('id', $row->id)->delete();

                continue;
            }

            $department = DB::table('departments')->where('name', $role->name)->first();

            if ($department === null) {
                $departmentId = DB::table('departments')->insertGetId([
                    'name' => $role->name,
                    'description' => $role->description,
                    'status' => $role->status ?? 'active',
                    'created_by' => $role->created_by,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            } else {
                $departmentId = $department->id;
            }

            $existing = DB::table('module_department')
                ->where('department_id', $departmentId)
                ->where('module_id', $row->module_id)
                ->where('id', '!=', $row->id)
                ->first();

            if ($existing !== null) {
                DB::table('module_department')
                    ->where('id', $existing->id)
                    ->update([
                        'can_create' => (bool) $existing->can_create || (bool) $row->can_create,
                        'can_read' => (bool) $existing->can_read || (bool) $row->can_read,
                        'can_update' => (bool) $existing->can_update || (bool) $row->can_update,
                        'can_delete' => (bool) $existing->can_delete || (bool) $row->can_delete,
                        'updated_at' => now(),
                    ]);

                DB::table('module_department')->where('id', $row->id)->delete();

                continue;
            }

            DB::table('module_department')
                ->where('id', $row->id)
                ->update(['department_id' => $departmentId]);
        }

        Schema::table('module_department', function (Blueprint $table) {
            $table->dropColumn('role_id');
        });

        Schema::table('module_department', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
        });

        $this->dropForeignKeysOn('module_department', 'department_id');
        $this->dropUniqueIndexesContaining('module_department', ['department_id', 'module_id']);

        Schema::table('module_department', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
            $table->unique(['department_id', 'module_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('module_department')) {
            return;
        }

        if (! Schema::hasColumn('module_department', 'department_id')) {
            Schema::rename('module_department', 'module_role');

            return;
        }

        $this->dropForeignKeysOn('module_department');
        $this->dropUniqueIndexesContaining('module_department', ['department_id', 'module_id']);

        Schema::table('module_department', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
        });

        foreach (DB::table('module_department')->orderBy('id')->get() as $row) {
            $department = DB::table('departments')->where('id', $row->department_id)->first();

            if ($department === null) {
                DB::table('module_department')->where('id', $row->id)->delete();

                continue;
            }

            $role = DB::table('roles')->where('name', $department->name)->first();

            if ($role === null) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $department->name,
                    'description' => $department->description,
                    'status' => $department->status ?? 'active',
                    'created_by' => $department->created_by,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            } else {
                $roleId = $role->id;
            }

            DB::table('module_department')
                ->where('id', $row->id)
                ->update(['role_id' => $roleId]);
        }

        Schema::table('module_department', function (Blueprint $table) {
            $table->dropColumn('department_id');
        });

        Schema::rename('module_department', 'module_role');

        Schema::table('module_role', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
            $table->unique(['role_id', 'module_id']);
        });
    }

    private function dropForeignKeysOn(string $table, ?string $column = null): void
    {
        $query = "
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ";

        $bindings = [$table];

        if ($column !== null) {
            $query .= ' AND COLUMN_NAME = ?';
            $bindings[] = $column;
        }

        foreach (DB::select($query, $bindings) as $foreignKey) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $table,
                $foreignKey->CONSTRAINT_NAME
            ));
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function dropUniqueIndexesContaining(string $table, array $columns): void
    {
        $indexes = DB::select("
            SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND NON_UNIQUE = 0
              AND INDEX_NAME <> 'PRIMARY'
            GROUP BY INDEX_NAME
        ", [$table]);

        $target = implode(',', $columns);

        foreach ($indexes as $index) {
            if ($index->cols === $target) {
                DB::statement(sprintf(
                    'ALTER TABLE `%s` DROP INDEX `%s`',
                    $table,
                    $index->INDEX_NAME
                ));
            }
        }
    }
};
