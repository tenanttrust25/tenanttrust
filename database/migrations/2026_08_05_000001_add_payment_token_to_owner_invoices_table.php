<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_invoices', function (Blueprint $table) {
            $table->string('payment_token', 64)->nullable()->unique()->after('file_path');
            $table->timestamp('paid_at')->nullable()->after('status');
        });

        // Backfill tokens for existing invoices so they can be paid immediately.
        $rows = DB::table('owner_invoices')->whereNull('payment_token')->get(['id']);
        foreach ($rows as $row) {
            DB::table('owner_invoices')
                ->where('id', $row->id)
                ->update(['payment_token' => Str::random(40)]);
        }
    }

    public function down(): void
    {
        Schema::table('owner_invoices', function (Blueprint $table) {
            $table->dropUnique(['payment_token']);
            $table->dropColumn(['payment_token', 'paid_at']);
        });
    }
};
