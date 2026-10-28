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
        Schema::create('proposals', function (Blueprint $table) {
            $table->string('id', 12)->primary(); // FRPR00000001 format
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('auth_person_id');
            $table->string('email');
            $table->string('service_id');
            $table->string('amount');
            $table->string('vat_amount');
            $table->string('created_by');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('business_id')
                ->references('id')
                ->on('followup_businesses')
                ->onDelete('cascade');

            $table->foreign('auth_person_id')
                ->references('id')
                ->on('followup_auth_persons')
                ->onDelete('restrict');

            $table->index('business_id');
            $table->index('auth_person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
