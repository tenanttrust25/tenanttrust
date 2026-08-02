<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('email');
            $table->string('gov_id_path')->nullable()->after('landlord_contact');
            $table->string('proof_of_employment_path')->nullable()->after('gov_id_path');
        });
    }

    public function down(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth', 'gov_id_path', 'proof_of_employment_path']);
        });
    }
};
