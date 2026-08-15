<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: This migration originally added all application-detail columns in one pass,
     * but a bad after() reference split it. The 21 columns below were already applied
     * to the live DB before the failure, so this file now describes exactly those.
     * The remaining 23 columns live in 2026_08_15_000002_*.
     */
    public function up(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            // Applicant
            $table->string('alternative_phone')->nullable()->after('phone');
            $table->date('desired_move_in_date')->nullable()->after('date_of_birth');

            // Co-applicant
            $table->string('co_applicant_name')->nullable()->after('desired_move_in_date');
            $table->string('co_applicant_email')->nullable()->after('co_applicant_name');
            $table->string('co_applicant_phone')->nullable()->after('co_applicant_email');
            $table->string('co_applicant_alt_phone')->nullable()->after('co_applicant_phone');
            $table->date('co_applicant_dob')->nullable()->after('co_applicant_alt_phone');

            // Other occupants
            $table->string('occupant_1_name')->nullable()->after('co_applicant_dob');
            $table->string('occupant_1_relation')->nullable()->after('occupant_1_name');
            $table->date('occupant_1_dob')->nullable()->after('occupant_1_relation');
            $table->string('occupant_2_name')->nullable()->after('occupant_1_dob');
            $table->string('occupant_2_relation')->nullable()->after('occupant_2_name');
            $table->date('occupant_2_dob')->nullable()->after('occupant_2_relation');

            // Current residence
            $table->string('current_address')->nullable()->after('rental_history');
            $table->string('current_duration')->nullable()->after('current_address');
            $table->string('current_monthly_rent')->nullable()->after('current_duration');
            $table->string('current_landlord_name')->nullable()->after('current_monthly_rent');
            $table->string('current_landlord_contact')->nullable()->after('current_landlord_name');
            $table->text('current_reason_leaving')->nullable()->after('current_landlord_contact');

            // Previous residence extras
            $table->string('previous_duration')->nullable()->after('previous_address');
            $table->string('previous_monthly_rent')->nullable()->after('previous_duration');
        });
    }

    public function down(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            $table->dropColumn([
                'alternative_phone',
                'desired_move_in_date',
                'co_applicant_name',
                'co_applicant_email',
                'co_applicant_phone',
                'co_applicant_alt_phone',
                'co_applicant_dob',
                'occupant_1_name',
                'occupant_1_relation',
                'occupant_1_dob',
                'occupant_2_name',
                'occupant_2_relation',
                'occupant_2_dob',
                'current_address',
                'current_duration',
                'current_monthly_rent',
                'current_landlord_name',
                'current_landlord_contact',
                'current_reason_leaving',
                'previous_duration',
                'previous_monthly_rent',
            ]);
        });
    }
};
