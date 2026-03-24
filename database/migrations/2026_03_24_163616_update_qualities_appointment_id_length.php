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
            $table->dropForeign(['appointment_id']);
            $table->string('appointment_id', 13)->change();
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qualities', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->string('appointment_id', 12)->change();
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });
    }
};
