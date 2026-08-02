@extends('layouts.app')

@section('title', 'Rental Application - Tenant Trust')

@section('content')
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Rental Application</h1>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width: 760px;">
            <div class="contact-section">
                <div class="contact-form" style="width:100%;">
                    @if ($errors->any())
                        <div class="alert-error" style="background:#fee2e2;color:#991b1b;padding:16px 20px;border-radius:8px;margin-bottom:24px;border:1px solid #fecaca;">
                            <strong>Please fix the following errors:</strong>
                            <ul style="margin:8px 0 0 16px;padding:0;">
                                @foreach ($errors->all() as $error)
                                    <li style="font-size:0.9rem;">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('rental-application.store') }}" enctype="multipart/form-data">
                        @csrf

                        <h3 style="font-family:'Manrope',sans-serif;font-size:1.1rem;color:#0F172A;margin-bottom:16px;">Your Information</h3>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                                @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                                @error('last_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="(705) 555-0123" required>
                                @error('phone') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}" required>
                            @error('date_of_birth') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <h3 style="font-family:'Manrope',sans-serif;font-size:1.1rem;color:#0F172A;margin:24px 0 16px;">Property You Are Applying For</h3>

                        <div class="form-group">
                            <label for="property_unit_id">Address You Are Applying For</label>
                            <select id="property_unit_id" name="property_unit_id" class="form-control @error('property_unit_id') is-invalid @enderror" required>
                                <option value="">-- Select a property --</option>
                                @foreach($units as $unitId => $unitLabel)
                                    <option value="{{ $unitId }}" @selected(old('property_unit_id', $preselectedUnit) == $unitId)>{{ $unitLabel }}</option>
                                @endforeach
                            </select>
                            @error('property_unit_id') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label for="previous_address">Previous Address</label>
                            <input type="text" id="previous_address" name="previous_address" class="form-control @error('previous_address') is-invalid @enderror" value="{{ old('previous_address') }}" placeholder="e.g. 12 Elm St, Sudbury, ON">
                            @error('previous_address') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="landlord_name">Landlord Name</label>
                                <input type="text" id="landlord_name" name="landlord_name" class="form-control @error('landlord_name') is-invalid @enderror" value="{{ old('landlord_name') }}" placeholder="e.g. Jane Smith">
                                @error('landlord_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="landlord_contact">Landlord Contact Info</label>
                                <input type="text" id="landlord_contact" name="landlord_contact" class="form-control @error('landlord_contact') is-invalid @enderror" value="{{ old('landlord_contact') }}" placeholder="Phone or email">
                                @error('landlord_contact') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h3 style="font-family:'Manrope',sans-serif;font-size:1.1rem;color:#0F172A;margin:24px 0 16px;">Household Details</h3>

                        <div class="form-group">
                            <label>Do you have any pets?</label>
                            <div style="display:flex;gap:32px;margin-top:8px;">
                                <label style="display:flex;align-items:center;gap:8px;font-weight:400;text-transform:none;letter-spacing:0;font-size:1rem;color:#333;margin:0;">
                                    <input type="radio" name="has_pets" value="1" @checked(old('has_pets') === '1') required> Yes
                                </label>
                                <label style="display:flex;align-items:center;gap:8px;font-weight:400;text-transform:none;letter-spacing:0;font-size:1rem;color:#333;margin:0;">
                                    <input type="radio" name="has_pets" value="0" @checked(old('has_pets') === '0' && old('has_pets') !== null) required> No
                                </label>
                            </div>
                            @error('has_pets') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group" id="pets-details-group" style="display:none;">
                            <label for="pets_details">Pet Details</label>
                            <input type="text" id="pets_details" name="pets_details" class="form-control" value="{{ old('pets_details') }}" placeholder="Type and breed, e.g. Cat (Domestic Shorthair)">
                            @error('pets_details') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label>Are you a smoker?</label>
                            <div style="display:flex;gap:32px;margin-top:8px;">
                                <label style="display:flex;align-items:center;gap:8px;font-weight:400;text-transform:none;letter-spacing:0;font-size:1rem;color:#333;margin:0;">
                                    <input type="radio" name="is_smoker" value="1" @checked(old('is_smoker') === '1') required> Yes
                                </label>
                                <label style="display:flex;align-items:center;gap:8px;font-weight:400;text-transform:none;letter-spacing:0;font-size:1rem;color:#333;margin:0;">
                                    <input type="radio" name="is_smoker" value="0" @checked(old('is_smoker') === '0' && old('is_smoker') !== null) required> No
                                </label>
                            </div>
                            @error('is_smoker') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <h3 style="font-family:'Manrope',sans-serif;font-size:1.1rem;color:#0F172A;margin:24px 0 16px;">Supporting Documents</h3>

                        <div class="form-group">
                            <label for="gov_id">Government Issued Photo ID <span style="color:#dc2626;">*</span></label>
                            <input type="file" id="gov_id" name="gov_id" class="form-control @error('gov_id') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" required>
                            <p style="font-size:0.85rem;color:#6b7280;margin-top:6px;">Driver's licence, passport, or provincial photo ID. Accepted: JPG, PNG, PDF (max 10 MB).</p>
                            @error('gov_id') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label for="proof_of_employment">Proof of Employment <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#6b7280;">(optional)</span></label>
                            <input type="file" id="proof_of_employment" name="proof_of_employment" class="form-control @error('proof_of_employment') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                            <p style="font-size:0.85rem;color:#6b7280;margin-top:6px;">Pay stub, letter of employment, or recent T4. Accepted: JPG, PNG, PDF (max 10 MB).</p>
                            @error('proof_of_employment') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <h3 style="font-family:'Manrope',sans-serif;font-size:1.1rem;color:#0F172A;margin:24px 0 16px;">Consent &amp; Authorization</h3>

                        <div style="background:#FAF5E5;border:1px solid #e2d5a8;border-radius:8px;padding:20px;margin-bottom:16px;">
                            <p style="font-size:0.95rem;color:#4A4A4A;line-height:1.7;margin-bottom:12px;">
                                By submitting this application, you authorize <strong>Tenant Trust</strong> to conduct a
                                <strong>soft credit check</strong> (a credit inquiry that does not affect your credit score)
                                and a <strong>criminal background check</strong> as part of the tenant screening process.
                                This information is used solely to evaluate your rental application and is handled in
                                accordance with applicable privacy laws.
                            </p>
                            <label style="display:flex;align-items:flex-start;gap:10px;font-weight:400;text-transform:none;letter-spacing:0;font-size:0.95rem;color:#333;margin:0;">
                                <input type="checkbox" name="consent_credit_check" value="1" @checked(old('consent_credit_check')) required style="margin-top:3px;">
                                <span>I consent to a <strong>soft credit check</strong> being performed.</span>
                            </label>
                            @error('consent_credit_check') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div style="background:#FAF5E5;border:1px solid #e2d5a8;border-radius:8px;padding:20px;margin-bottom:24px;">
                            <label style="display:flex;align-items:flex-start;gap:10px;font-weight:400;text-transform:none;letter-spacing:0;font-size:0.95rem;color:#333;margin:0;">
                                <input type="checkbox" name="consent_background_check" value="1" @checked(old('consent_background_check')) required style="margin-top:3px;">
                                <span>I consent to a <strong>criminal background check</strong> being performed.</span>
                            </label>
                            @error('consent_background_check') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <button type="submit" class="btn btn-gold">Submit Application</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        const petsRadios = document.querySelectorAll('input[name="has_pets"]');
        const petsDetailsGroup = document.getElementById('pets-details-group');
        const petsDetailsInput = document.getElementById('pets_details');
        function togglePetsDetails() {
            const show = document.querySelector('input[name="has_pets"]:checked')?.value === '1';
            petsDetailsGroup.style.display = show ? 'block' : 'none';
            if (!show) { petsDetailsInput.value = ''; }
        }
        petsRadios.forEach(r => r.addEventListener('change', togglePetsDetails));
        togglePetsDetails();
    </script>
@endsection
