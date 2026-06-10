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
        Schema::create('business_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('followup_business_id')->nullable();
            $table->text('interested_services')->nullable()->comment('Comma-separated service IDs');
            $table->unsignedBigInteger('primary_service_id')->nullable();
            $table->string('current_agency')->nullable();
            $table->decimal('current_monthly_spend', 15, 2)->nullable();
            $table->decimal('planned_monthly_budget', 15, 2)->nullable();
            $table->string('existing_website_platform')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('followup_business_id')
                ->references('id')
                ->on('followup_businesses')
                ->onDelete('cascade');

            $table->foreign('primary_service_id')
                ->references('id')
                ->on('services')
                ->onDelete('set null');

            $table->index('followup_business_id');
            $table->index('primary_service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_services');
    }
};
