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
        Schema::table('followup_auth_persons', function (Blueprint $table) {
            $table->string('seniority_level', 100)->nullable()->after('job_title');
            $table->string('extension', 50)->nullable()->after('seniority_level');
            $table->string('linkedin_profile')->nullable()->after('extension');
            $table->string('facebook_profile')->nullable()->after('linkedin_profile');
            $table->string('preferred_contact_method', 100)->nullable()->after('facebook_profile');
            $table->string('preferred_contact_time')->nullable()->after('preferred_contact_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followup_auth_persons', function (Blueprint $table) {
            $table->dropColumn([
                'seniority_level',
                'extension',
                'linkedin_profile',
                'facebook_profile',
                'preferred_contact_method',
                'preferred_contact_time',
            ]);
        });
    }
};
