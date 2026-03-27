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
        Schema::table('qualities', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->after('auditstatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qualities', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};
