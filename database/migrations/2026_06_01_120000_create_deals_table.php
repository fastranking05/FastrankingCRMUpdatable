<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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
            $table->string('priority')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('followup_business_id')
                ->references('id')
                ->on('followup_businesses')
                ->onDelete('cascade');

            // Links to followup_auth_persons (contact on the deal)
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
