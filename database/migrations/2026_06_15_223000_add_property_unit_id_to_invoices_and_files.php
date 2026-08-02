<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add property_unit_id to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('property_unit_id')->nullable()->after('property_id')
                ->constrained('property_units')->nullOnDelete();
            $table->index('property_unit_id');
        });

        // Add property_unit_id to tenant_files
        Schema::table('tenant_files', function (Blueprint $table) {
            $table->foreignId('property_unit_id')->nullable()->after('tenant_id')
                ->constrained('property_units')->nullOnDelete();
            $table->index('property_unit_id');
        });

        // Add property_unit_id to maintenance_tickets
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->foreignId('property_unit_id')->nullable()->after('property_id')
                ->constrained('property_units')->nullOnDelete();
            $table->index('property_unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('property_unit_id');
        });
        Schema::table('tenant_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('property_unit_id');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('property_unit_id');
        });
    }
};
