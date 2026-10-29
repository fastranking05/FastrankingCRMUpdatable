<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move proposal line items to proposal_services so a proposal can have
     * multiple services. Existing single-service data is backfilled, then the
     * legacy columns are dropped from proposals.
     */
    public function up(): void
    {
        Schema::create('proposal_services', function (Blueprint $table) {
            $table->id();
            $table->string('proposal_id', 12);
            $table->unsignedBigInteger('service_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('cascade');

            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->onDelete('restrict');

            $table->index('proposal_id');
            $table->index('service_id');
        });

        DB::table('proposals')
            ->whereNotNull('service_id')
            ->orderBy('id')
            ->chunk(500, function ($proposals) {
                foreach ($proposals as $proposal) {
                    DB::table('proposal_services')->insert([
                        'proposal_id' => $proposal->id,
                        'service_id' => (int) $proposal->service_id,
                        'amount' => (float) ($proposal->amount ?? 0),
                        'vat_amount' => (float) ($proposal->vat_amount ?? 0),
                        'created_at' => $proposal->created_at,
                        'updated_at' => $proposal->updated_at,
                    ]);
                }
            });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['service_id', 'amount', 'vat_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('service_id')->nullable()->after('email');
            $table->string('amount')->nullable()->after('service_id');
            $table->string('vat_amount')->nullable()->after('amount');
        });

        DB::table('proposal_services')->orderBy('proposal_id')->chunk(500, function ($items) {
            foreach ($items as $item) {
                DB::table('proposals')
                    ->where('id', $item->proposal_id)
                    ->whereNull('service_id')
                    ->update([
                        'service_id' => (string) $item->service_id,
                        'amount' => (string) $item->amount,
                        'vat_amount' => (string) $item->vat_amount,
                    ]);
            }
        });

        Schema::dropIfExists('proposal_services');
    }
};
