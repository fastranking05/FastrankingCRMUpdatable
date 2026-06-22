<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_services', function (Blueprint $table) {
            $table->string('current_monthly_spend', 30)->nullable()->change();
            $table->string('planned_monthly_budget', 30)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_services', function (Blueprint $table) {
            $table->decimal('current_monthly_spend', 15, 2)->nullable()->change();
            $table->decimal('planned_monthly_budget', 15, 2)->nullable()->change();
        });
    }
};
