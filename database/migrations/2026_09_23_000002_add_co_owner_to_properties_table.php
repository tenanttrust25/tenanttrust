<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Optional second owner (owner + co-owner). Nullable; existing data
            // keeps a single owner.
            $table->unsignedBigInteger('co_owner_id')->nullable()->after('owner_id');
            $table->index('co_owner_id');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['co_owner_id']);
            $table->dropColumn('co_owner_id');
        });
    }
};
