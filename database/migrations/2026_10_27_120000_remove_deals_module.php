<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the Deals module: drop proposals.deal_id, drop the deals table,
     * and swap the Deals permission module for a Proposals one.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
            $table->dropIndex(['deal_id']);
            $table->dropColumn('deal_id');
        });

        Schema::dropIfExists('deals');

        DB::table('global_search_documents')->where('entity_type', 'deal')->delete();

        $dealsModule = DB::table('modules')->where('name', 'Deals')->first();

        $proposalsModuleId = DB::table('modules')->where('name', 'Proposals')->value('id');

        if (! $proposalsModuleId) {
            $proposalsModuleId = DB::table('modules')->insertGetId([
                'name' => 'Proposals',
                'description' => 'Sales proposals linked to businesses and services',
                'status' => 'active',
                'created_by' => $dealsModule->created_by ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($dealsModule) {
            $permissions = DB::table('module_department')
                ->where('module_id', $dealsModule->id)
                ->get();

            foreach ($permissions as $permission) {
                DB::table('module_department')->updateOrInsert(
                    [
                        'module_id' => $proposalsModuleId,
                        'department_id' => $permission->department_id,
                    ],
                    [
                        'can_create' => $permission->can_create,
                        'can_read' => $permission->can_read,
                        'can_update' => $permission->can_update,
                        'can_delete' => $permission->can_delete,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::table('module_department')->where('module_id', $dealsModule->id)->delete();
            DB::table('modules')->where('id', $dealsModule->id)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->string('id', 15)->primary();
            $table->unsignedBigInteger('followup_business_id');
            $table->unsignedBigInteger('auth_person_id')->nullable();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('deal_stage')->nullable();
            $table->text('lost_reason')->nullable();
            $table->decimal('probability', 5, 2)->nullable();
            $table->date('estimated_closed_date')->nullable();
            $table->string('selected_service')->nullable();
            $table->decimal('amount_exc_vat', 15, 2)->nullable();
            $table->decimal('vat', 15, 2)->nullable();
            $table->string('next_activity')->nullable();
            $table->date('callback_date')->nullable();
            $table->time('callback_time')->nullable();
            $table->string('priority')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('followup_business_id')
                ->references('id')
                ->on('followup_businesses')
                ->onDelete('cascade');

            $table->foreign('auth_person_id')
                ->references('id')
                ->on('followup_auth_persons')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('followup_business_id');
            $table->index('auth_person_id');
            $table->index('deal_stage');
            $table->index('created_by');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->string('deal_id', 15)->nullable()->after('auth_person_id');
            $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
            $table->index('deal_id');
        });
    }
};
