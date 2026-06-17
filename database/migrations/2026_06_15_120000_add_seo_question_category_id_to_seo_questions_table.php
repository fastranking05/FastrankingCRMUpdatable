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
        Schema::table('seo_questions', function (Blueprint $table) {
            $table->foreignId('seo_question_category_id')
                ->nullable()
                ->after('name')
                ->constrained('seo_question_categories')
                ->nullOnDelete();

            $table->index('seo_question_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_questions', function (Blueprint $table) {
            $table->dropForeign(['seo_question_category_id']);
            $table->dropColumn('seo_question_category_id');
        });
    }
};
