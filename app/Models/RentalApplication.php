<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalApplication extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'alternative_phone',
        'email',
        'date_of_birth',
        'desired_move_in_date',
        'address_applying_for',
        'property_unit_id',
        'rental_history',
        'previous_address',
        'landlord_name',
        'landlord_contact',
        'gov_id_path',
        'proof_of_employment_path',
        'has_pets',
        'pets_details',
        'is_smoker',
        'consent_credit_check',
        'consent_background_check',
        'status',
        'decline_reasons',
        'decline_notes',
        // Co-applicant
        'co_applicant_name',
        'co_applicant_email',
        'co_applicant_phone',
        'co_applicant_alt_phone',
        'co_applicant_dob',
        // Other occupants
        'occupant_1_name',
        'occupant_1_relation',
        'occupant_1_dob',
        'occupant_2_name',
        'occupant_2_relation',
        'occupant_2_dob',
        // Rental history — current residence
        'current_address',
        'current_duration',
        'current_monthly_rent',
        'current_landlord_name',
        'current_landlord_contact',
        'current_reason_leaving',
        // Rental history — previous residence
        'previous_duration',
        'previous_monthly_rent',
        'previous_reason_leaving',
        // Screening questions
        'ever_evicted',
        'missed_payments',
        'refused_rent',
        'rental_issues_explanation',
        // Employment — current
        'employment_status',
        'employer_name',
        'supervisor_name',
        'supervisor_phone',
        'job_title',
        'date_hired',
        'monthly_income',
        'other_income',
        // Employment — previous
        'previous_employer',
        'previous_supervisor',
        'previous_supervisor_phone',
        'previous_job_title',
        'employment_period',
        // Consent comments + declaration
        'credit_check_comments',
        'criminal_check_comments',
        'applicant_signature',
        'co_applicant_signature',
        'signature_date',
    ];

    protected $casts = [
        'has_pets' => 'boolean',
        'is_smoker' => 'boolean',
        'consent_credit_check' => 'boolean',
        'consent_background_check' => 'boolean',
        'date_of_birth' => 'date',
        'desired_move_in_date' => 'date',
        'co_applicant_dob' => 'date',
        'occupant_1_dob' => 'date',
        'occupant_2_dob' => 'date',
        'ever_evicted' => 'boolean',
        'missed_payments' => 'boolean',
        'refused_rent' => 'boolean',
        'date_hired' => 'date',
        'signature_date' => 'date',
        'decline_reasons' => 'array',
    ];

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'property_unit_id');
    }

    /**
     * The owner (landlord) of the property this application is for, when the
     * unit is linked to a property that has an owner on file.
     */
    public function owner(): ?User
    {
        return $this->propertyUnit?->property?->owner;
    }

    /**
     * A valid owner email address for this application, or null.
     */
    public function ownerEmail(): ?string
    {
        $email = $this->owner()?->email;

        return ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null;
    }

    /**
     * The co-owner of the property this application is for, when set.
     */
    public function coOwner(): ?User
    {
        return $this->propertyUnit?->property?->coOwner;
    }

    /**
     * Every valid owner/co-owner email for this application (unique).
     */
    public function ownerEmails(): array
    {
        $emails = [];

        foreach ([$this->owner(), $this->coOwner()] as $user) {
            $email = $user?->email;
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
