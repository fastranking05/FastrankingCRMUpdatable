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
        Schema::create('lead_qualifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('followup_business_id');
            $table->string('temperature')->nullable();
            $table->boolean('budget')->default(0);
            $table->boolean('authority')->default(0);
            $table->boolean('need')->default(0);
            $table->boolean('timeline')->default(0);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('followup_business_id')
                ->references('id')
                ->on('followup_businesses')
                ->onDelete('cascade');

            $table->unique('followup_business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_qualifications');
    }
};
