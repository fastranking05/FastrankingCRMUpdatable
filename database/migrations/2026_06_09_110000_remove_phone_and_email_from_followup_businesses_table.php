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
            $table->dropColumn(['phone', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followup_businesses', function (Blueprint $table) {
            $table->string('phone')->unique()->nullable()->after('website');
            $table->string('email')->nullable()->after('phone');
        });
    }
};
