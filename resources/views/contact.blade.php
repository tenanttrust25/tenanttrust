@extends('layouts.app')

@section('title', 'Contact Us | Tenant Trust Property Management Sudbury')
@section('meta_description', 'Get in touch with Tenant Trust property management in Sudbury, Ontario. Call 249-523-3331 or email info@tenanttrust.ca.')

@section('content')
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Contact Us</h1>
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

                    <form method="POST" action="{{ route('contact.submit') }}">
                        @csrf
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
                            <textarea id="message" name="message" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                            @error('message') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label for="captcha">Security check: What is {{ $captcha['question'] }}?</label>
                            <input type="text" id="captcha" name="captcha" class="form-control @error('captcha') is-invalid @enderror" placeholder="Type your answer" autocomplete="off" inputmode="numeric" required>
                            <div class="form-hint">Prove you're not a robot — answer in numbers (e.g. 4).</div>
                            @error('captcha') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-gold">Send Message</button>
                    </form>
                </div>

                <!-- Contact Info -->
                <div class="contact-info">
                    <h3>Get In Touch</h3>
                    <p style="color:#4A4A4A;margin-bottom:32px;line-height:1.8;">
                        Have a question about a property or interested in our management services? We'd love to hear from you.
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
