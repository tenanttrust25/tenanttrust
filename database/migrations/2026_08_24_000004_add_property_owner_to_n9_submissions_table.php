<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('n9_submissions', function (Blueprint $table) {
            $table->foreignId('property_id')->nullable()->after('tenant_id')->constrained('properties')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->after('property_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('n9_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('property_id');
            $table->dropConstrainedForeignId('owner_id');
        });
    }
};
