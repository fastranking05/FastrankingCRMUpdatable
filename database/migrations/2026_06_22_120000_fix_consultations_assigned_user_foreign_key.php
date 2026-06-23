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
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['assigned_user']);
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_user')->nullable()->change();
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->foreign('assigned_user')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['assigned_user']);
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_user')->nullable(false)->change();
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->foreign('assigned_user')
                ->references('id')
                ->on('users');
        });
    }
};
