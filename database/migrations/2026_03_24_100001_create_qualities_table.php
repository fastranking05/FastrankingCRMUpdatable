<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qualities', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_id', 13)->required();
            $table->enum('auditstatus', ['qualified', 'unqualified', 'pending'])->default('unqualified')->required();
            $table->string('status')->default('QA-Pending')->required();
            $table->unsignedBigInteger('assigned_user')->required();
            $table->string('meeting_link')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('assigned_user')->references('id')->on('users')->onDelete('cascade');
            $table->index(['appointment_id', 'assigned_user']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qualities');
    }
};
