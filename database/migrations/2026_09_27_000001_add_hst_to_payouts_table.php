<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('payouts', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('management_fee');
            }
            if (! Schema::hasColumn('payouts', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('tax_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'tax_rate']);
        });
    }
};
