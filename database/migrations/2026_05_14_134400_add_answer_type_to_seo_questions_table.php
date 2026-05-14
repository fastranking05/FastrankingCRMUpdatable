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
            $table->string('answer_type')->default('text')->after('name')->comment('text, textarea, number, date, dropdown');
            $table->json('dropdown_options')->nullable()->after('answer_type')->comment('JSON array of options when answer_type is dropdown');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_questions', function (Blueprint $table) {
            $table->dropColumn(['answer_type', 'dropdown_options']);
        });
    }
};
