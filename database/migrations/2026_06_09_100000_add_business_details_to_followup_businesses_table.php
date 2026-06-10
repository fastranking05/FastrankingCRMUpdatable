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
        Schema::table('followup_businesses', function (Blueprint $table) {
            $table->string('trading_name')->nullable()->after('name');
            $table->string('company_registration_number', 100)->nullable()->after('trading_name');
            $table->text('address')->nullable()->after('company_registration_number');
            $table->string('company_size', 100)->nullable()->after('address');
            $table->string('sub_category')->nullable()->after('category');
            $table->decimal('annual_revenue', 15, 2)->nullable()->after('company_size');
            $table->unsignedInteger('number_of_locations')->nullable()->after('annual_revenue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followup_businesses', function (Blueprint $table) {
            $table->dropColumn([
                'trading_name',
                'company_registration_number',
                'address',
                'company_size',
                'sub_category',
                'annual_revenue',
                'number_of_locations',
            ]);
        });
    }
};
