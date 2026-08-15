<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            $table->text('previous_reason_leaving')->nullable()->after('previous_monthly_rent');

            // Screening questions
            $table->boolean('ever_evicted')->default(false)->after('previous_reason_leaving');
            $table->boolean('missed_payments')->default(false)->after('ever_evicted');
            $table->boolean('refused_rent')->default(false)->after('missed_payments');
            $table->text('rental_issues_explanation')->nullable()->after('refused_rent');

            // Employment — current
            $table->string('employment_status')->nullable()->after('rental_issues_explanation');
            $table->string('employer_name')->nullable()->after('employment_status');
            $table->string('supervisor_name')->nullable()->after('employer_name');
            $table->string('supervisor_phone')->nullable()->after('supervisor_name');
            $table->string('job_title')->nullable()->after('supervisor_phone');
            $table->date('date_hired')->nullable()->after('job_title');
            $table->string('monthly_income')->nullable()->after('date_hired');
            $table->text('other_income')->nullable()->after('monthly_income');

            // Employment — previous
            $table->string('previous_employer')->nullable()->after('other_income');
            $table->string('previous_supervisor')->nullable()->after('previous_employer');
            $table->string('previous_supervisor_phone')->nullable()->after('previous_supervisor');
            $table->string('previous_job_title')->nullable()->after('previous_supervisor_phone');
            $table->string('employment_period')->nullable()->after('previous_job_title');

            // Consent comments + declaration
            $table->text('credit_check_comments')->nullable()->after('consent_background_check');
            $table->text('criminal_check_comments')->nullable()->after('credit_check_comments');
            $table->string('applicant_signature')->nullable()->after('criminal_check_comments');
            $table->string('co_applicant_signature')->nullable()->after('applicant_signature');
            $table->date('signature_date')->nullable()->after('co_applicant_signature');
        });
    }

    public function down(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            $table->dropColumn([
                'previous_reason_leaving',
                'ever_evicted',
                'missed_payments',
                'refused_rent',
                'rental_issues_explanation',
                'employment_status',
                'employer_name',
                'supervisor_name',
                'supervisor_phone',
                'job_title',
                'date_hired',
                'monthly_income',
                'other_income',
                'previous_employer',
                'previous_supervisor',
                'previous_supervisor_phone',
                'previous_job_title',
                'employment_period',
                'credit_check_comments',
                'criminal_check_comments',
                'applicant_signature',
                'co_applicant_signature',
                'signature_date',
            ]);
        });
    }
};
