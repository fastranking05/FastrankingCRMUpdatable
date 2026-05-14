<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_question_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seo_details_id');
            $table->unsignedBigInteger('seo_question_id');
            $table->text('answer');
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            
            $table->foreign('seo_details_id')->references('id')->on('seo_details')->onDelete('cascade');
            $table->foreign('seo_question_id')->references('id')->on('seo_questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_question_answers');
    }
};
