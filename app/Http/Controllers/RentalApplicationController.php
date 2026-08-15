<?php

namespace App\Http\Controllers;

use App\Mail\RentalApplicationMail;
use App\Models\PropertyUnit;
use App\Models\RentalApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RentalApplicationController extends Controller
{
    public function create(Request $request)
    {
        $units = PropertyUnit::with('property')
            ->where('status', 'active')
            ->orderBy('unit')
            ->get()
            ->mapWithKeys(fn ($unit) => [
                $unit->id => ($unit->property?->title ?? 'Property') . ' — Unit ' . $unit->unit,
            ]);

        return view('rental-application', [
            'units' => $units,
            'preselectedUnit' => $request->integer('unit', 0),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'date_of_birth' => 'required|date|before:today',
            'property_unit_id' => 'required|exists:property_units,id',
            'previous_address' => 'nullable|string|max:255',
            'landlord_name' => 'nullable|string|max:255',
            'landlord_contact' => 'nullable|string|max:255',
            'gov_id' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'proof_of_employment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'has_pets' => 'required|in:0,1',
            'pets_details' => 'nullable|string|max:255',
            'is_smoker' => 'required|in:0,1',
            'consent_credit_check' => 'required|accepted',
            'consent_background_check' => 'required|accepted',

            // Applicant extras
            'alternative_phone' => 'nullable|string|max:20',
            'desired_move_in_date' => 'nullable|date',

            // Co-applicant
            'co_applicant_name' => 'nullable|string|max:255',
            'co_applicant_email' => 'nullable|email|max:255',
            'co_applicant_phone' => 'nullable|string|max:20',
            'co_applicant_alt_phone' => 'nullable|string|max:20',
            'co_applicant_dob' => 'nullable|date|before:today',

            // Other occupants
            'occupant_1_name' => 'nullable|string|max:255',
            'occupant_1_relation' => 'nullable|string|max:255',
            'occupant_1_dob' => 'nullable|date',
            'occupant_2_name' => 'nullable|string|max:255',
            'occupant_2_relation' => 'nullable|string|max:255',
            'occupant_2_dob' => 'nullable|date',

            // Current residence
            'current_address' => 'nullable|string|max:255',
            'current_duration' => 'nullable|string|max:255',
            'current_monthly_rent' => 'nullable|string|max:50',
            'current_landlord_name' => 'nullable|string|max:255',
            'current_landlord_contact' => 'nullable|string|max:255',
            'current_reason_leaving' => 'nullable|string',

            // Previous residence extras
            'previous_duration' => 'nullable|string|max:255',
            'previous_monthly_rent' => 'nullable|string|max:50',
            'previous_reason_leaving' => 'nullable|string',

            // Screening questions
            'ever_evicted' => 'nullable|in:0,1',
            'missed_payments' => 'nullable|in:0,1',
            'refused_rent' => 'nullable|in:0,1',
            'rental_issues_explanation' => 'nullable|string',

            // Employment — current
            'employment_status' => 'nullable|in:full_time,part_time,student,unemployed,retired',
            'employer_name' => 'nullable|string|max:255',
            'supervisor_name' => 'nullable|string|max:255',
            'supervisor_phone' => 'nullable|string|max:20',
            'job_title' => 'nullable|string|max:255',
            'date_hired' => 'nullable|date',
            'monthly_income' => 'nullable|string|max:50',
            'other_income' => 'nullable|string',

            // Employment — previous
            'previous_employer' => 'nullable|string|max:255',
            'previous_supervisor' => 'nullable|string|max:255',
            'previous_supervisor_phone' => 'nullable|string|max:20',
            'previous_job_title' => 'nullable|string|max:255',
            'employment_period' => 'nullable|string|max:255',

            // Consent comments + declaration
            'credit_check_comments' => 'nullable|string',
            'criminal_check_comments' => 'nullable|string',
            'applicant_signature' => 'nullable|string|max:255',
            'co_applicant_signature' => 'nullable|string|max:255',
            'signature_date' => 'nullable|date',
        ]);

        $unit = PropertyUnit::with('property')->findOrFail($validated['property_unit_id']);
        $address = trim(implode(', ', array_filter([
            $unit->property?->address,
            $unit->property?->city,
            $unit->property?->province,
        ])) . ' — Unit ' . $unit->unit);

        $application = RentalApplication::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'date_of_birth' => $validated['date_of_birth'],
            'property_unit_id' => $validated['property_unit_id'],
            'address_applying_for' => $address,
            'previous_address' => $validated['previous_address'] ?? null,
            'landlord_name' => $validated['landlord_name'] ?? null,
            'landlord_contact' => $validated['landlord_contact'] ?? null,
            'gov_id_path' => $request->file('gov_id')?->store('rental-applications/gov-id', 'public'),
            'proof_of_employment_path' => $request->file('proof_of_employment')?->store('rental-applications/employment', 'public'),
            'has_pets' => (bool) $validated['has_pets'],
            'pets_details' => $validated['pets_details'] ?? null,
            'is_smoker' => (bool) $validated['is_smoker'],
            'consent_credit_check' => true,
            'consent_background_check' => true,

            // Applicant extras
            'alternative_phone' => $validated['alternative_phone'] ?? null,
            'desired_move_in_date' => $validated['desired_move_in_date'] ?? null,

            // Co-applicant
            'co_applicant_name' => $validated['co_applicant_name'] ?? null,
            'co_applicant_email' => $validated['co_applicant_email'] ?? null,
            'co_applicant_phone' => $validated['co_applicant_phone'] ?? null,
            'co_applicant_alt_phone' => $validated['co_applicant_alt_phone'] ?? null,
            'co_applicant_dob' => $validated['co_applicant_dob'] ?? null,

            // Other occupants
            'occupant_1_name' => $validated['occupant_1_name'] ?? null,
            'occupant_1_relation' => $validated['occupant_1_relation'] ?? null,
            'occupant_1_dob' => $validated['occupant_1_dob'] ?? null,
            'occupant_2_name' => $validated['occupant_2_name'] ?? null,
            'occupant_2_relation' => $validated['occupant_2_relation'] ?? null,
            'occupant_2_dob' => $validated['occupant_2_dob'] ?? null,

            // Current residence
            'current_address' => $validated['current_address'] ?? null,
            'current_duration' => $validated['current_duration'] ?? null,
            'current_monthly_rent' => $validated['current_monthly_rent'] ?? null,
            'current_landlord_name' => $validated['current_landlord_name'] ?? null,
            'current_landlord_contact' => $validated['current_landlord_contact'] ?? null,
            'current_reason_leaving' => $validated['current_reason_leaving'] ?? null,

            // Previous residence extras
            'previous_duration' => $validated['previous_duration'] ?? null,
            'previous_monthly_rent' => $validated['previous_monthly_rent'] ?? null,
            'previous_reason_leaving' => $validated['previous_reason_leaving'] ?? null,

            // Screening questions
            'ever_evicted' => (bool) ($validated['ever_evicted'] ?? false),
            'missed_payments' => (bool) ($validated['missed_payments'] ?? false),
            'refused_rent' => (bool) ($validated['refused_rent'] ?? false),
            'rental_issues_explanation' => $validated['rental_issues_explanation'] ?? null,

            // Employment — current
            'employment_status' => $validated['employment_status'] ?? null,
            'employer_name' => $validated['employer_name'] ?? null,
            'supervisor_name' => $validated['supervisor_name'] ?? null,
            'supervisor_phone' => $validated['supervisor_phone'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'date_hired' => $validated['date_hired'] ?? null,
            'monthly_income' => $validated['monthly_income'] ?? null,
            'other_income' => $validated['other_income'] ?? null,

            // Employment — previous
            'previous_employer' => $validated['previous_employer'] ?? null,
            'previous_supervisor' => $validated['previous_supervisor'] ?? null,
            'previous_supervisor_phone' => $validated['previous_supervisor_phone'] ?? null,
            'previous_job_title' => $validated['previous_job_title'] ?? null,
            'employment_period' => $validated['employment_period'] ?? null,

            // Consent comments + declaration
            'credit_check_comments' => $validated['credit_check_comments'] ?? null,
            'criminal_check_comments' => $validated['criminal_check_comments'] ?? null,
            'applicant_signature' => $validated['applicant_signature'] ?? null,
            'co_applicant_signature' => $validated['co_applicant_signature'] ?? null,
            'signature_date' => $validated['signature_date'] ?? null,
        ]);

        // Notify admin
        try {
            Mail::mailer("smtp")
                ->to("info@tenanttrust.ca")
                ->send(new RentalApplicationMail($application));
        } catch (\Exception $e) {
            // Email failure shouldn't block the applicant
        }

        return redirect()
            ->route('rental-application.thank-you')
            ->with('application', $application);
    }

    public function thankYou()
    {
        return view('rental-application-thank-you');
    }
}
