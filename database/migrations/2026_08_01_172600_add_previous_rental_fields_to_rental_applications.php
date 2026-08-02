<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            $table->string('previous_address')->nullable()->after('rental_history');
            $table->string('landlord_name')->nullable()->after('previous_address');
            $table->string('landlord_contact')->nullable()->after('landlord_name');
        });
    }

    public function down(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            $table->dropColumn(['previous_address', 'landlord_name', 'landlord_contact']);
        });
    }
};
