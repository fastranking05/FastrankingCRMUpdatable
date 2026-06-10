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
            $table->string('sub_source', 50)->nullable()->after('source_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followup_businesses', function (Blueprint $table) {
            $table->dropColumn('sub_source');
        });
    }
};
