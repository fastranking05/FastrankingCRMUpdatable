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
            $table->text('challenges')->nullable()->after('previous_services')->comment('Comma-separated values');
            $table->text('expectation')->nullable()->after('challenges')->comment('Comma-separated values');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_services', function (Blueprint $table) {
            $table->dropColumn(['challenges', 'expectation']);
        });
    }
};
