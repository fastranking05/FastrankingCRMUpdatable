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
        Schema::create('followup_business_auth_person', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('followup_business_id');
            $table->unsignedBigInteger('followup_auth_person_id');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('followup_business_id')->references('id')->on('followup_businesses')->onDelete('cascade');
            $table->foreign('followup_auth_person_id')->references('id')->on('followup_auth_persons')->onDelete('cascade');
            
            $table->unique(['followup_business_id', 'followup_auth_person_id'], 'fb_ap_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followup_business_auth_person');
    }
};
