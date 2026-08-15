@extends('layouts.app')

@section('title', 'Rental Application - Tenant Trust')

@section('content')
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Rental Application</h1>
            <p class="page-header-sub" style="color:#cbd5e1;font-size:1.1rem;margin-top:8px;">Apply to rent a Tenant Trust property</p>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width: 860px;">

            {{-- Contact banner --}}
            <div style="background:#FAF5E5;border:1px solid #e2d5a8;border-radius:10px;padding:20px 24px;margin-bottom:32px;display:flex;flex-wrap:wrap;gap:32px;align-items:center;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="font-size:1.4rem;">✉️</span>
                    <div>
                        <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:#8a7a45;font-weight:600;">Questions?</div>
                        <a href="mailto:info@tenanttrust.ca" style="color:#0F172A;font-weight:600;text-decoration:none;">info@tenanttrust.ca</a>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="font-size:1.4rem;">📞</span>
                    <div>
                        <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:#8a7a45;font-weight:600;">Call us</div>
                        <a href="tel:+12495233331" style="color:#0F172A;font-weight:600;text-decoration:none;">(249) 523-3331</a>
                    </div>
                </div>
                <div style="flex:1;min-width:200px;text-align:right;font-size:0.85rem;color:#4A4A4A;">
                    Please complete every applicable section. Supporting documents can be attached below.
                </div>
            </div>

            <div class="contact-section" style="grid-template-columns:1fr;">
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

                        {{-- ============ 1. RENTAL PROPERTY ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">1</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Rental Property</h3>
                        </div>

                        <div class="form-group">
                            <label for="property_unit_id">Property You Are Applying For <span style="color:#dc2626;">*</span></label>
                            <select id="property_unit_id" name="property_unit_id" class="form-control @error('property_unit_id') is-invalid @enderror" required>
                                <option value="">-- Select a property --</option>
                                @foreach($units as $unitId => $unitLabel)
                                    <option value="{{ $unitId }}" @selected(old('property_unit_id', $preselectedUnit) == $unitId)>{{ $unitLabel }}</option>
                                @endforeach
                            </select>
                            @error('property_unit_id') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="desired_move_in_date">Desired Move-In Date</label>
                                <input type="date" id="desired_move_in_date" name="desired_move_in_date" class="form-control @error('desired_move_in_date') is-invalid @enderror" value="{{ old('desired_move_in_date') }}">
                                @error('desired_move_in_date') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth <span style="color:#dc2626;">*</span></label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}" required>
                                @error('date_of_birth') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- ============ 2. APPLICANT ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin:40px 0 20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">2</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Applicant's Personal Information</h3>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="first_name">First Name <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                                @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                                @error('last_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="phone">Home Phone <span style="color:#dc2626;">*</span></label>
                                <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="(705) 555-0123" required>
                                @error('phone') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="alternative_phone">Alternative Phone</label>
                                <input type="tel" id="alternative_phone" name="alternative_phone" class="form-control @error('alternative_phone') is-invalid @enderror" value="{{ old('alternative_phone') }}" placeholder="(705) 555-0123">
                                @error('alternative_phone') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span style="color:#dc2626;">*</span></label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        {{-- ============ 3. CO-APPLICANT ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin:40px 0 20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">3</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Co-Applicant's Information <span style="font-size:0.85rem;font-weight:400;color:#6b7280;">(if applicable)</span></h3>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="co_applicant_name">Full Name</label>
                                <input type="text" id="co_applicant_name" name="co_applicant_name" class="form-control @error('co_applicant_name') is-invalid @enderror" value="{{ old('co_applicant_name') }}">
                                @error('co_applicant_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="co_applicant_email">Email Address</label>
                                <input type="email" id="co_applicant_email" name="co_applicant_email" class="form-control @error('co_applicant_email') is-invalid @enderror" value="{{ old('co_applicant_email') }}">
                                @error('co_applicant_email') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="co_applicant_phone">Home Phone</label>
                                <input type="tel" id="co_applicant_phone" name="co_applicant_phone" class="form-control @error('co_applicant_phone') is-invalid @enderror" value="{{ old('co_applicant_phone') }}" placeholder="(705) 555-0123">
                                @error('co_applicant_phone') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="co_applicant_alt_phone">Alternative Phone</label>
                                <input type="tel" id="co_applicant_alt_phone" name="co_applicant_alt_phone" class="form-control @error('co_applicant_alt_phone') is-invalid @enderror" value="{{ old('co_applicant_alt_phone') }}" placeholder="(705) 555-0123">
                                @error('co_applicant_alt_phone') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="co_applicant_dob">Date of Birth</label>
                                <input type="date" id="co_applicant_dob" name="co_applicant_dob" class="form-control @error('co_applicant_dob') is-invalid @enderror" value="{{ old('co_applicant_dob') }}">
                                @error('co_applicant_dob') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- ============ 4. OTHER OCCUPANTS ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin:40px 0 20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">4</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Other Occupants <span style="font-size:0.85rem;font-weight:400;color:#6b7280;">(if applicable)</span></h3>
                        </div>

                        @for ($i = 1; $i <= 2; $i++)
                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px;margin-bottom:16px;">
                                <div style="font-family:'Manrope',sans-serif;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin-bottom:14px;">Occupant {{ $i }}</div>
                                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="occupant_{{ $i }}_name">Name</label>
                                        <input type="text" id="occupant_{{ $i }}_name" name="occupant_{{ $i }}_name" class="form-control" value="{{ old('occupant_' . $i . '_name') }}">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="occupant_{{ $i }}_relation">Relation to Applicant</label>
                                        <input type="text" id="occupant_{{ $i }}_relation" name="occupant_{{ $i }}_relation" class="form-control" value="{{ old('occupant_' . $i . '_relation') }}" placeholder="e.g. Spouse, Child">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="occupant_{{ $i }}_dob">Date of Birth</label>
                                        <input type="date" id="occupant_{{ $i }}_dob" name="occupant_{{ $i }}_dob" class="form-control" value="{{ old('occupant_' . $i . '_dob') }}">
                                    </div>
                                </div>
                            </div>
                        @endfor

                        {{-- ============ 5. RENTAL HISTORY ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin:40px 0 20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">5</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Rental History</h3>
                        </div>

                        <h4 style="font-family:'Manrope',sans-serif;font-size:0.95rem;color:#0F172A;margin:0 0 16px;">I. Current Residence</h4>

                        <div class="form-group">
                            <label for="current_address">Current Address</label>
                            <input type="text" id="current_address" name="current_address" class="form-control @error('current_address') is-invalid @enderror" value="{{ old('current_address') }}" placeholder="123 Main St, Sudbury, ON">
                            @error('current_address') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="current_duration">How long have you resided here?</label>
                                <input type="text" id="current_duration" name="current_duration" class="form-control @error('current_duration') is-invalid @enderror" value="{{ old('current_duration') }}" placeholder="e.g. 2 years">
                                @error('current_duration') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="current_monthly_rent">Monthly Rent</label>
                                <input type="text" id="current_monthly_rent" name="current_monthly_rent" class="form-control @error('current_monthly_rent') is-invalid @enderror" value="{{ old('current_monthly_rent') }}" placeholder="$1,200">
                                @error('current_monthly_rent') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="current_landlord_name">Landlord's Name</label>
                                <input type="text" id="current_landlord_name" name="current_landlord_name" class="form-control @error('current_landlord_name') is-invalid @enderror" value="{{ old('current_landlord_name') }}">
                                @error('current_landlord_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="current_landlord_contact">Landlord's Contact Number</label>
                                <input type="text" id="current_landlord_contact" name="current_landlord_contact" class="form-control @error('current_landlord_contact') is-invalid @enderror" value="{{ old('current_landlord_contact') }}" placeholder="(705) 555-0123">
                                @error('current_landlord_contact') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="current_reason_leaving">Reason(s) for Leaving This Property</label>
                                <input type="text" id="current_reason_leaving" name="current_reason_leaving" class="form-control @error('current_reason_leaving') is-invalid @enderror" value="{{ old('current_reason_leaving') }}">
                                @error('current_reason_leaving') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h4 style="font-family:'Manrope',sans-serif;font-size:0.95rem;color:#0F172A;margin:28px 0 16px;">II. Previous Residence <span style="font-weight:400;color:#6b7280;">(if applicable)</span></h4>

                        <div class="form-group">
                            <label for="previous_address">Previous Address</label>
                            <input type="text" id="previous_address" name="previous_address" class="form-control @error('previous_address') is-invalid @enderror" value="{{ old('previous_address') }}" placeholder="456 Oak Ave, Sudbury, ON">
                            @error('previous_address') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="previous_duration">How long did you stay?</label>
                                <input type="text" id="previous_duration" name="previous_duration" class="form-control @error('previous_duration') is-invalid @enderror" value="{{ old('previous_duration') }}" placeholder="e.g. 1 year">
                                @error('previous_duration') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="previous_monthly_rent">Monthly Rent</label>
                                <input type="text" id="previous_monthly_rent" name="previous_monthly_rent" class="form-control @error('previous_monthly_rent') is-invalid @enderror" value="{{ old('previous_monthly_rent') }}" placeholder="$1,000">
                                @error('previous_monthly_rent') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="landlord_name">Landlord's Name</label>
                                <input type="text" id="landlord_name" name="landlord_name" class="form-control @error('landlord_name') is-invalid @enderror" value="{{ old('landlord_name') }}">
                                @error('landlord_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="landlord_contact">Landlord's Contact Number</label>
                                <input type="text" id="landlord_contact" name="landlord_contact" class="form-control @error('landlord_contact') is-invalid @enderror" value="{{ old('landlord_contact') }}" placeholder="(705) 555-0123">
                                @error('landlord_contact') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="previous_reason_leaving">Reason(s) for Leaving</label>
                                <input type="text" id="previous_reason_leaving" name="previous_reason_leaving" class="form-control @error('previous_reason_leaving') is-invalid @enderror" value="{{ old('previous_reason_leaving') }}">
                                @error('previous_reason_leaving') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Screening questions --}}
                        <div style="background:#FAF5E5;border:1px solid #e2d5a8;border-radius:8px;padding:20px;margin-top:24px;">
                            <div style="font-family:'Manrope',sans-serif;font-weight:700;color:#0F172A;margin-bottom:14px;">Screening Questions</div>

                            @foreach ([
                                'ever_evicted' => 'Have you ever been evicted from a rental residence?',
                                'missed_payments' => 'Have you missed two or more rental payments in the past 12 months?',
                                'refused_rent' => 'Have you ever refused to pay rent when due?',
                            ] as $field => $question)
                                <div style="margin-bottom:12px;">
                                    <div style="font-size:0.95rem;color:#333;margin-bottom:6px;">{{ $question }}</div>
                                    <div style="display:flex;gap:32px;">
                                        <label style="display:flex;align-items:center;gap:8px;font-weight:400;text-transform:none;letter-spacing:0;font-size:1rem;color:#333;margin:0;">
                                            <input type="radio" name="{{ $field }}" value="1" @checked(old($field) === '1')> Yes
                                        </label>
                                        <label style="display:flex;align-items:center;gap:8px;font-weight:400;text-transform:none;letter-spacing:0;font-size:1rem;color:#333;margin:0;">
                                            <input type="radio" name="{{ $field }}" value="0" @checked(old($field) === '0')> No
                                        </label>
                                    </div>
                                </div>
                            @endforeach

                            <div class="form-group" style="margin:14px 0 0;">
                                <label for="rental_issues_explanation">If you answered YES to any of the above, please explain:</label>
                                <textarea id="rental_issues_explanation" name="rental_issues_explanation" class="form-control @error('rental_issues_explanation') is-invalid @enderror" style="min-height:90px;">{{ old('rental_issues_explanation') }}</textarea>
                                @error('rental_issues_explanation') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- ============ 6. EMPLOYMENT ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin:40px 0 20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">6</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Employment Details</h3>
                        </div>

                        <h4 style="font-family:'Manrope',sans-serif;font-size:0.95rem;color:#0F172A;margin:0 0 16px;">I. Current Employment</h4>

                        <div class="form-group">
                            <label>Employment Status</label>
                            <div style="display:flex;flex-wrap:wrap;gap:20px;margin-top:8px;">
                                @foreach ([
                                    'full_time' => 'Full-Time',
                                    'part_time' => 'Part-time',
                                    'student' => 'Student',
                                    'unemployed' => 'Unemployed',
                                    'retired' => 'Retired',
                                ] as $val => $label)
                                    <label style="display:flex;align-items:center;gap:8px;font-weight:400;text-transform:none;letter-spacing:0;font-size:1rem;color:#333;margin:0;">
                                        <input type="radio" name="employment_status" value="{{ $val }}" @checked(old('employment_status') === $val)> {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('employment_status') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="employer_name">Current Employer</label>
                                <input type="text" id="employer_name" name="employer_name" class="form-control @error('employer_name') is-invalid @enderror" value="{{ old('employer_name') }}">
                                @error('employer_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="job_title">Job Title</label>
                                <input type="text" id="job_title" name="job_title" class="form-control @error('job_title') is-invalid @enderror" value="{{ old('job_title') }}">
                                @error('job_title') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="supervisor_name">Supervisor's Name</label>
                                <input type="text" id="supervisor_name" name="supervisor_name" class="form-control @error('supervisor_name') is-invalid @enderror" value="{{ old('supervisor_name') }}">
                                @error('supervisor_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="supervisor_phone">Supervisor's Phone</label>
                                <input type="tel" id="supervisor_phone" name="supervisor_phone" class="form-control @error('supervisor_phone') is-invalid @enderror" value="{{ old('supervisor_phone') }}" placeholder="(705) 555-0123">
                                @error('supervisor_phone') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="date_hired">Date Hired</label>
                                <input type="date" id="date_hired" name="date_hired" class="form-control @error('date_hired') is-invalid @enderror" value="{{ old('date_hired') }}">
                                @error('date_hired') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="monthly_income">Monthly Income</label>
                                <input type="text" id="monthly_income" name="monthly_income" class="form-control @error('monthly_income') is-invalid @enderror" value="{{ old('monthly_income') }}" placeholder="$3,000">
                                @error('monthly_income') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="other_income">Other Sources of Income</label>
                                <input type="text" id="other_income" name="other_income" class="form-control @error('other_income') is-invalid @enderror" value="{{ old('other_income') }}" placeholder="e.g. child support, investments">
                                @error('other_income') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h4 style="font-family:'Manrope',sans-serif;font-size:0.95rem;color:#0F172A;margin:28px 0 16px;">II. Previous Employment <span style="font-weight:400;color:#6b7280;">(if any)</span></h4>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="previous_employer">Previous Employer</label>
                                <input type="text" id="previous_employer" name="previous_employer" class="form-control @error('previous_employer') is-invalid @enderror" value="{{ old('previous_employer') }}">
                                @error('previous_employer') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="previous_job_title">Job Title</label>
                                <input type="text" id="previous_job_title" name="previous_job_title" class="form-control @error('previous_job_title') is-invalid @enderror" value="{{ old('previous_job_title') }}">
                                @error('previous_job_title') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="previous_supervisor">Supervisor's Name</label>
                                <input type="text" id="previous_supervisor" name="previous_supervisor" class="form-control @error('previous_supervisor') is-invalid @enderror" value="{{ old('previous_supervisor') }}">
                                @error('previous_supervisor') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="previous_supervisor_phone">Supervisor's Phone</label>
                                <input type="tel" id="previous_supervisor_phone" name="previous_supervisor_phone" class="form-control @error('previous_supervisor_phone') is-invalid @enderror" value="{{ old('previous_supervisor_phone') }}" placeholder="(705) 555-0123">
                                @error('previous_supervisor_phone') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="employment_period">Period of Employment</label>
                                <input type="text" id="employment_period" name="employment_period" class="form-control @error('employment_period') is-invalid @enderror" value="{{ old('employment_period') }}" placeholder="e.g. 2022 – 2024">
                                @error('employment_period') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- ============ 7. DOCUMENTS ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin:40px 0 20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">7</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Proof of Income &amp; Documents</h3>
                        </div>

                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px 20px;margin-bottom:16px;">
                            <p style="font-size:0.9rem;color:#4A4A4A;line-height:1.7;margin:0 0 14px;">
                                Acceptable documentation includes pay stubs, an employer's letter/certificate, bank statements, or a copy of the previous year's tax return.
                            </p>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="gov_id">Government Issued Photo ID <span style="color:#dc2626;">*</span></label>
                                <input type="file" id="gov_id" name="gov_id" class="form-control @error('gov_id') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" required>
                                <p style="font-size:0.85rem;color:#6b7280;margin-top:6px;">Driver's licence, passport, or provincial photo ID. JPG, PNG, PDF (max 10 MB).</p>
                                @error('gov_id') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="proof_of_employment">Proof of Income <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#6b7280;">(optional)</span></label>
                                <input type="file" id="proof_of_employment" name="proof_of_employment" class="form-control @error('proof_of_employment') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                                <p style="font-size:0.85rem;color:#6b7280;margin-top:6px;">Pay stub, letter of employment, bank statement, or tax return. JPG, PNG, PDF (max 10 MB).</p>
                                @error('proof_of_employment') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- ============ 8. CONSENT ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin:40px 0 20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">8</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Credit &amp; Background Check Authorization</h3>
                        </div>

                        <div style="background:#FAF5E5;border:1px solid #e2d5a8;border-radius:8px;padding:20px;margin-bottom:16px;">
                            <p style="font-size:0.95rem;color:#4A4A4A;line-height:1.7;margin-bottom:12px;">
                                By submitting this application, you authorize <strong>Tenant Trust</strong> to conduct a
                                <strong>soft credit check</strong> (does not affect your credit score) and a
                                <strong>criminal background check</strong> as part of the tenant screening process.
                            </p>
                            <label style="display:flex;align-items:flex-start;gap:10px;font-weight:400;text-transform:none;letter-spacing:0;font-size:0.95rem;color:#333;margin:0;">
                                <input type="checkbox" name="consent_credit_check" value="1" @checked(old('consent_credit_check')) required style="margin-top:3px;">
                                <span>I consent to a <strong>soft credit check</strong> being performed. <span style="color:#dc2626;">*</span></span>
                            </label>
                            @error('consent_credit_check') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label for="credit_check_comments">Anything our credit check may find that you'd like to comment on?</label>
                            <textarea id="credit_check_comments" name="credit_check_comments" class="form-control @error('credit_check_comments') is-invalid @enderror" style="min-height:80px;">{{ old('credit_check_comments') }}</textarea>
                            @error('credit_check_comments') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div style="background:#FAF5E5;border:1px solid #e2d5a8;border-radius:8px;padding:20px;margin:16px 0;">
                            <label style="display:flex;align-items:flex-start;gap:10px;font-weight:400;text-transform:none;letter-spacing:0;font-size:0.95rem;color:#333;margin:0;">
                                <input type="checkbox" name="consent_background_check" value="1" @checked(old('consent_background_check')) required style="margin-top:3px;">
                                <span>I consent to a <strong>criminal background check</strong> being performed. <span style="color:#dc2626;">*</span></span>
                            </label>
                            @error('consent_background_check') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label for="criminal_check_comments">Anything our criminal check may find that you'd like to comment on?</label>
                            <textarea id="criminal_check_comments" name="criminal_check_comments" class="form-control @error('criminal_check_comments') is-invalid @enderror" style="min-height:80px;">{{ old('criminal_check_comments') }}</textarea>
                            @error('criminal_check_comments') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        {{-- ============ 9. HOUSEHOLD ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin:40px 0 20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">9</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Additional Information</h3>
                        </div>

                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
                            <p style="font-size:0.9rem;color:#4A4A4A;line-height:1.7;margin:0;">
                                <strong>Property policies:</strong> The Landlord does not allow pets, smoking (cigarettes or marijuana), or waterbeds in the rental property.
                            </p>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
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
                        </div>

                        <div class="form-group" id="pets-details-group" style="display:none;">
                            <label for="pets_details">Pet Details</label>
                            <input type="text" id="pets_details" name="pets_details" class="form-control" value="{{ old('pets_details') }}" placeholder="Type and breed, e.g. Cat (Domestic Shorthair)">
                            @error('pets_details') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        {{-- ============ 10. DECLARATION ============ --}}
                        <div style="display:flex;align-items:center;gap:14px;margin:40px 0 20px;padding-bottom:12px;border-bottom:2px solid #F0E6C5;">
                            <span style="width:36px;height:36px;border-radius:50%;background:#DCA54A;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope',sans-serif;font-weight:700;">10</span>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.25rem;color:#0F172A;margin:0;">Declaration &amp; Signature</h3>
                        </div>

                        <div style="background:#FAF5E5;border:1px solid #e2d5a8;border-radius:8px;padding:20px;margin-bottom:24px;">
                            <p style="font-size:0.9rem;color:#4A4A4A;line-height:1.8;margin:0 0 12px;">
                                I declare that the information I have provided is true and correct, and contains no misrepresentations.
                                If misrepresentations are found after a residential lease agreement is entered into between the Landlord
                                and the Applicant, the Landlord shall have the option to terminate the residential lease agreement
                                and seek all available remedies.
                            </p>
                            <p style="font-size:0.9rem;color:#4A4A4A;line-height:1.8;margin:0;">
                                The Applicant authorizes the Landlord to verify all references and facts, including but not limited to
                                current and previous landlords, employers and personal references. The Applicant understands that
                                incomplete or incorrect information may cause a delay in processing or may result in the denial of the application.
                            </p>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="applicant_signature">Applicant's Signature <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#6b7280;">(type full name)</span></label>
                                <input type="text" id="applicant_signature" name="applicant_signature" class="form-control @error('applicant_signature') is-invalid @enderror" value="{{ old('applicant_signature') }}" placeholder="Jane Smith">
                                @error('applicant_signature') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="co_applicant_signature">Co-Applicant's Signature <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#6b7280;">(if applicable)</span></label>
                                <input type="text" id="co_applicant_signature" name="co_applicant_signature" class="form-control @error('co_applicant_signature') is-invalid @enderror" value="{{ old('co_applicant_signature') }}" placeholder="John Smith">
                                @error('co_applicant_signature') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="signature_date">Date</label>
                            <input type="date" id="signature_date" name="signature_date" class="form-control @error('signature_date') is-invalid @enderror" value="{{ old('signature_date', date('Y-m-d')) }}">
                            @error('signature_date') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div style="margin-top:32px;text-align:center;">
                            <button type="submit" class="btn btn-gold" style="font-size:1.05rem;padding:16px 48px;">Submit Application</button>
                            <p style="font-size:0.85rem;color:#6b7280;margin-top:12px;">By submitting, you consent to the credit and background checks described above.</p>
                        </div>
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
