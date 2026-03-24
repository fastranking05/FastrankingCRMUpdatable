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
        Schema::create('quality_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quality_id')->required();
            $table->unsignedBigInteger('question_id')->required();
            $table->enum('answers', ['yes', 'no', 'partially done', 'not applicable'])->required();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('quality_id')->references('id')->on('qualities')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('quality_questions')->onDelete('cascade');
            $table->index(['quality_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_answers');
    }
};
