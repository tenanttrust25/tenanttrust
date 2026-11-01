<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which payment settled this invoice via rolled-forward credit.
        // NULL = normally paid (or unpaid); set = marked paid by another
        // invoice's over-payment, so it can be reversed cleanly.
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_source_payment_id')->nullable()->after('status');
            $table->index('credit_source_payment_id');
        });

        // Unapplied surplus left on a payment after it covered its own invoice
        // and any later invoices. Applied to future invoices as they are created.
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('credit_remaining', 10, 2)->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['credit_source_payment_id']);
            $table->dropColumn('credit_source_payment_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('credit_remaining');
        });
    }
};
