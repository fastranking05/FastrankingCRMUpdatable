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
        Schema::table('business_services', function (Blueprint $table) {
            $table->unsignedTinyInteger('previous_experience')->nullable()->after('existing_website_platform');
            $table->text('previous_services')->nullable()->after('previous_experience')->comment('Comma-separated service IDs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_services', function (Blueprint $table) {
            $table->dropColumn(['previous_experience', 'previous_services']);
        });
    }
};
