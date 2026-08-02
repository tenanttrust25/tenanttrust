<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('management_fee_type')->nullable()->after('unit_count')
                ->comment('percentage or flat');
            $table->decimal('management_fee_value', 10, 2)->nullable()->after('management_fee_type')
                ->comment('e.g., 8.00 for 8%, or 100.00 for $100 flat');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['management_fee_type', 'management_fee_value']);
        });
    }
};
