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
        Schema::table('proposal_services', function (Blueprint $table) {
            $table->decimal('estimation_duration', 8, 2)->nullable()->after('vat_amount');
            $table->string('estimation_unit', 50)->nullable()->after('estimation_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_services', function (Blueprint $table) {
            $table->dropColumn(['estimation_duration', 'estimation_unit']);
        });
    }
};
