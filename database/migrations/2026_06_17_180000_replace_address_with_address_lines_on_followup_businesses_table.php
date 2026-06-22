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
        Schema::table('followup_businesses', function (Blueprint $table) {
            $table->string('address_line1', 50)->nullable()->after('company_registration_number');
            $table->string('address_line2', 50)->nullable()->after('address_line1');
            $table->string('city', 50)->nullable()->after('address_line2');
            $table->string('postcode', 50)->nullable()->after('city');
            $table->string('country', 50)->nullable()->after('postcode');
        });

        if (Schema::hasColumn('followup_businesses', 'address')) {
            foreach (DB::table('followup_businesses')->whereNotNull('address')->cursor() as $row) {
                DB::table('followup_businesses')
                    ->where('id', $row->id)
                    ->update([
                        'address_line1' => mb_substr((string) $row->address, 0, 50),
                    ]);
            }

            Schema::table('followup_businesses', function (Blueprint $table) {
                $table->dropColumn('address');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followup_businesses', function (Blueprint $table) {
            $table->text('address')->nullable()->after('company_registration_number');
        });

        foreach (DB::table('followup_businesses')->cursor() as $row) {
            $legacyAddress = collect([
                $row->address_line1,
                $row->address_line2,
                $row->city,
                $row->postcode,
                $row->country,
            ])->filter()->implode(', ');

            if ($legacyAddress !== '') {
                DB::table('followup_businesses')
                    ->where('id', $row->id)
                    ->update(['address' => $legacyAddress]);
            }
        }

        Schema::table('followup_businesses', function (Blueprint $table) {
            $table->dropColumn([
                'address_line1',
                'address_line2',
                'city',
                'postcode',
                'country',
            ]);
        });
    }
};
