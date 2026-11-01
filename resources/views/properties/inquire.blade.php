@extends('layouts.app')

@section('title', 'Inquire About ' . $property->title . ' | Tenant Trust Property Management Sudbury')
@section('meta_description', 'Inquire about ' . $property->address . ', ' . $property->city . '. Send your question to Tenant Trust property management and we will get back to you shortly.')

@section('content')
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Inquire About This Property</h1>
            <p style="color:#cbd5e1;margin-top:8px;">Send us a question about the unit below and we'll get back to you shortly.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="contact-section">
                <!-- Contact Form -->
                <div class="contact-form">
                    @if(session('success'))
                        <div class="alert-success">{{ session('success') }}</div>
                    @endif

                    @php
                        $unitLabel = $activeUnit?->unit ? 'Unit ' . $activeUnit->unit : null;
                        $prefill = "Hi, I'm interested in {$property->title}"
                            . ($unitLabel ? " ({$unitLabel})" : '')
                            . " at {$property->address}, {$property->city}"
                            . ($activeUnit?->rent_amount ? " — listed at $" . number_format($activeUnit->rent_amount) . '/mo' : '')
                            . ". Can I book a viewing?";
                    @endphp

                    <form method="POST" action="{{ route('contact.submit') }}">
                        @csrf
                        {{-- Property context carried through from the listing page --}}
                        <input type="hidden" name="property_id" value="{{ $property->id }}">
                        <input type="hidden" name="property_unit_id" value="{{ $activeUnit?->id }}">

                        <!-- Honeypot: invisible to humans; bots auto-fill it. If filled, the server silently drops the submission. -->
                        <div style="position:absolute !important; left:-9999px !important; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
                            <label for="company_website">Company Website</label>
                            <input type="text" id="company_website" name="company_website" tabindex="-1" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number (optional)</label>
                            <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                            @error('phone') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control @error('message') is-invalid @enderror" required>{{ old('message', $prefill) }}</textarea>
                            @error('message') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label for="captcha">Security check: What is {{ $captcha['question'] }}?</label>
                            <input type="text" id="captcha" name="captcha" class="form-control @error('captcha') is-invalid @enderror" placeholder="Type your answer" autocomplete="off" inputmode="numeric" required>
                            <div class="form-hint">Prove you're not a robot — answer in numbers (e.g. 4).</div>
                            @error('captcha') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-gold">Send Inquiry</button>
                    </form>
                </div>

                <!-- Property Summary + Contact Info -->
                <div class="contact-info">
                    <h3>You're inquiring about</h3>
                    <div style="margin:16px 0 28px;padding:18px;background:#FFFFFF;border:1px solid #E2E8F0;border-radius:10px;">
                        <p style="font-family:'Manrope',sans-serif;font-weight:700;color:#0F172A;font-size:1.05rem;margin-bottom:6px;">{{ $property->title }}</p>
                        <p style="color:#4A4A4A;margin-bottom:4px;">{{ $property->address }}, {{ $property->city }}, {{ $property->province }}</p>
                        @if($unitLabel)
                            <p style="color:#4A4A4A;margin-bottom:4px;">{{ $unitLabel }}</p>
                        @endif
                        <p style="color:#0F172A;font-weight:600;margin-top:8px;">
                            {{ $activeUnit?->rent_amount ? '$' . number_format($activeUnit->rent_amount) . '/mo' : $property->formatted_price }}
                        </p>
                        <a href="{{ route('properties.show', $property->slug) }}" style="display:inline-block;margin-top:12px;color:#DCA54A;font-weight:600;">← Back to listing</a>
                    </div>

                    <h3>Get In Touch</h3>
                    <p style="color:#4A4A4A;margin-bottom:32px;line-height:1.8;">
                        Prefer to reach us directly? We're happy to help.
                    </p>

                    <div class="contact-info-item">
                        <div class="contact-info-icon">📧</div>
                        <div class="contact-info-text">
                            <h4>Email</h4>
                            <p><a href="mailto:info@tenanttrust.ca" style="color:#DCA54A;">info@tenanttrust.ca</a></p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-info-icon">📞</div>
                        <div class="contact-info-text">
                            <h4>Phone</h4>
                            <p><a href="tel:+12495233331" style="color:#DCA54A; font-weight:600;">249-523-3331</a></p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-info-icon">📍</div>
                        <div class="contact-info-text">
                            <h4>Location</h4>
                            <p>474 McKim St Unit 3, Sudbury, ON P3C 2L6</p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-info-icon">🕐</div>
                        <div class="contact-info-text">
                            <h4>Hours</h4>
                            <p>Monday - Friday: 9:00 AM - 5:00 PM<br>Weekends: By appointment</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
