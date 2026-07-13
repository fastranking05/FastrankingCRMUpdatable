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
            $table->string('primaryphone_country_code', 10)->nullable()->after('primaryphone');
            $table->string('altphone_country_code', 10)->nullable()->after('altphone');
            $table->string('primarymobile_country_code', 10)->nullable()->after('primarymobile');
            $table->string('altmobile_country_code', 10)->nullable()->after('altmobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followup_auth_persons', function (Blueprint $table) {
            $table->dropColumn([
                'primaryphone_country_code',
                'altphone_country_code',
                'primarymobile_country_code',
                'altmobile_country_code',
            ]);
        });
    }
};
