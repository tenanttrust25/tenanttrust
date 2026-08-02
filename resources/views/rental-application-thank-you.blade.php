@extends('layouts.app')

@section('title', 'Application Received - Tenant Trust')

@section('content')
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Application Received</h1>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width: 700px;">
            <div style="background:#d1fae5;border:1px solid #a7f3d0;border-radius:8px;padding:32px;text-align:center;">
                <div style="font-size:3rem;margin-bottom:16px;">✅</div>
                <h2 style="font-family:'Manrope',sans-serif;color:#065f46;margin-bottom:12px;">Thank You!</h2>
                <p style="color:#065f46;font-size:1.05rem;line-height:1.7;margin-bottom:8px;">
                    Your rental application has been received. Our team will review it shortly
                    and contact you at the email or phone number you provided.
                </p>
                <p style="color:#047857;font-size:0.95rem;margin-bottom:24px;">
                    Please note: a soft credit check and criminal background check will be performed as part of
                    the tenant screening process, as outlined in the application.
                </p>
                <a href="/properties" class="btn btn-gold">Browse More Properties</a>
            </div>
        </div>
    </section>
@endsection
