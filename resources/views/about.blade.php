@extends('layouts.app')

@section('title', 'About Us | Tenant Trust Property Management Sudbury')
@section('meta_description', 'Tenant Trust is a dedicated property management company serving property owners and tenants across Greater Sudbury and area.')

@section('content')
    <!-- About Hero -->
    <section class="about-hero">
        <div class="container">
            <h1>About <span>Tenant Trust</span></h1>
            <p>We are a dedicated property management company committed to providing exceptional service to both property owners and tenants across Greater Sudbury and Area.</p>
        </div>
    </section>

    <!-- Our Story -->
    <section class="section">
        <div class="container">
            <div class="about-grid">
                <div class="about-text">
                    <h2>Our Story</h2>
                    <div class="gold-accent" style="margin:0 0 20px 0;"></div>
                    <p>Tenant Trust was founded with a simple mission: to make property management transparent, professional, and stress-free. We believe that great properties deserve great management.</p>
                    <p>We understand the unique challenges of the Canadian rental market and work tirelessly to create win-win solutions for owners and tenants alike.</p>
                    <p>Whether you're looking for your next rental home or need professional management for your investment property, Tenant Trust is here to help.</p>
                </div>
                <div class="about-image">
                    🏢
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="section section-light">
        <div class="container">
            <div class="gold-accent"></div>
            <h2 class="section-title">Our Core Values</h2>
            <p class="section-subtitle">The principles that guide everything we do</p>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-card-icon">🤝</div>
                    <h3>Integrity</h3>
                    <p>We operate with honesty and transparency in every transaction, building lasting trust with our clients.</p>
                </div>
                <div class="value-card">
                    <div class="value-card-icon">⭐</div>
                    <h3>Excellence</h3>
                    <p>We strive for the highest standards in property management, from maintenance to tenant relations.</p>
                </div>
                <div class="value-card">
                    <div class="value-card-icon">💡</div>
                    <h3>Innovation</h3>
                    <p>We leverage modern technology and best practices to streamline operations and enhance the tenant experience.</p>
                </div>
                <div class="value-card">
                    <div class="value-card-icon">❤️</div>
                    <h3>Community</h3>
                    <p>We believe in building strong communities through responsible property management and tenant engagement.</p>
                </div>
                <div class="value-card">
                    <div class="value-card-icon">📈</div>
                    <h3>Growth</h3>
                    <p>We are committed to continuous improvement, always finding new ways to serve our clients better.</p>
                </div>
                <div class="value-card">
                    <div class="value-card-icon">🌿</div>
                    <h3>Sustainability</h3>
                    <p>We promote sustainable practices in property management for a better future.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container">
            <h2>Let's Work <span>Together</span></h2>
            <p>Whether you're a property owner or looking for your next home, we'd love to hear from you.</p>
            <a href="/contact" class="btn btn-gold">Get In Touch</a>
        </div>
    </section>
@endsection
