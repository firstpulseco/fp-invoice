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
        Schema::table('business_settings', function (Blueprint $table) {
            $table->json('payment_methods')->nullable()->after('payment_details');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->json('payment_methods')->nullable()->after('payment_details');
        });

        $this->backfillPaymentMethods('business_settings');
        $this->backfillPaymentMethods('invoices');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('payment_methods');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_methods');
        });
    }

    private function backfillPaymentMethods(string $table): void
    {
        DB::table($table)
            ->where(function ($query): void {
                $query->whereNotNull('payment_method_name')->orWhereNotNull('payment_details');
            })
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($table): void {
                foreach ($records as $record) {
                    DB::table($table)->where('id', $record->id)->update([
                        'payment_methods' => json_encode([[
                            'name' => $record->payment_method_name ?? '',
                            'details' => $record->payment_details ?? '',
                        ]], JSON_THROW_ON_ERROR),
                    ]);
                }
            });
    }
};
