@extends('layouts.app')

@section('title', 'Tenant Trust - Find Your Perfect Rental')

@section('content')
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg">
            <div class="placeholder" style="width:100%;height:100%;background:url('https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1920&q=80') center/cover no-repeat;"></div>
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <h1>Find Your <span>Perfect</span> Rental</h1>
            <p>Discover exceptional properties managed with care. From cozy apartments to spacious family homes — we help you find the place that feels right.</p>
            <div class="hero-buttons">
                <a href="/properties" class="btn btn-gold">Browse Properties</a>
                <a href="/contact" class="btn btn-outline">Contact Us</a>
            </div>
        </div>
    </section>

    <!-- Featured Properties -->
    @if($featuredProperties->count() > 0)
    <section class="section">
        <div class="container">
            <div class="gold-accent"></div>
            <h2 class="section-title">Featured Properties</h2>
            <p class="section-subtitle">Hand-picked selections from our finest listings</p>
            <div class="property-grid">
                @foreach($featuredProperties as $property)
                    <a href="{{ route('properties.show', $property->slug) }}" class="property-card">
                        <div class="property-card-image">
                            @if($property->photo)
                                <img src="{{ asset('storage/' . $property->photo) }}" alt="{{ $property->title }}">
                            @else
                                <div class="placeholder">🏠</div>
                            @endif
                            <span class="property-card-badge">{{ $property->property_type === 'for-rent' ? 'For Rent' : 'For Sale' }}</span>
                        </div>
                        <div class="property-card-content">
                            <h3>{{ $property->title }}</h3>
                            <p class="property-card-address">{{ $property->address }}, {{ $property->city }}</p>
                            <div class="property-card-details">
                                <span>🛏️ {{ $property->bedrooms }}</span>
                                <span>🚿 {{ $property->bathrooms }}</span>
                                @if($property->square_feet)
                                    <span>📐 {{ number_format($property->square_feet) }} sqft</span>
                                @endif
                            </div>
                            <div class="property-card-price">{{ $property->formatted_price }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Why Choose Us -->
    <section class="section section-light">
        <div class="container">
            <div class="gold-accent"></div>
            <h2 class="section-title">Why Choose Tenant Trust</h2>
            <p class="section-subtitle">We put our clients first, every step of the way</p>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-card-icon">🔑</div>
                    <h3>Trusted Expertise</h3>
                    <p>Years of experience in property management, ensuring your investment is in capable hands.</p>
                </div>
                <div class="value-card">
                    <div class="value-card-icon">🤝</div>
                    <h3>Personal Service</h3>
                    <p>Every client receives dedicated attention and customized solutions for their unique needs.</p>
                </div>
                <div class="value-card">
                    <div class="value-card-icon">⭐</div>
                    <h3>Proven Results</h3>
                    <p>Our track record speaks for itself. Happy tenants, satisfied owners, thriving properties.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Find Your <span>Next Home</span>?</h2>
            <p>Browse our available properties or get in touch with our team for personalized assistance.</p>
            <a href="/properties" class="btn btn-gold">View All Properties</a>
        </div>
    </section>
@endsection
