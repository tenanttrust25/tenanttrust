<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table) {
            // Optional property context: set when a visitor uses the
            // "Inquire About This Property" flow on a listing page.
            $table->foreignId('property_id')->nullable()->after('phone')
                ->constrained('properties')->nullOnDelete();
            $table->foreignId('property_unit_id')->nullable()->after('property_id')
                ->constrained('property_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('property_unit_id');
            $table->dropConstrainedForeignId('property_id');
        });
    }
};
