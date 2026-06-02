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
        Schema::table('followup_details', function (Blueprint $table) {
            if (Schema::hasColumn('followup_details', 'source')) {
                $table->dropColumn('source');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'source')) {
                $table->dropColumn('source');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followup_details', function (Blueprint $table) {
            if (!Schema::hasColumn('followup_details', 'source')) {
                $table->string('source')->nullable()->after('followup_business_id');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'source')) {
                $table->string('source', 255)->nullable()->after('followup_business_id');
            }
        });
    }
};
