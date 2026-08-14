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
