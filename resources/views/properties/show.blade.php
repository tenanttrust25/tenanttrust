@extends('layouts.app')

@section('title', $property->title . ' - Tenant Trust')

@push('styles')
<style>
.gallery-thumbs {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    flex-wrap: wrap;
}
.gallery-thumb {
    width: 80px;
    height: 60px;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    padding: 0;
    background: none;
    transition: border-color 0.2s ease, opacity 0.2s ease;
    opacity: 0.6;
}
.gallery-thumb:hover {
    opacity: 0.9;
}
.gallery-thumb.active {
    border-color: #DCA54A;
    opacity: 1;
}
.gallery-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
</style>
@endpush

@section('content')
    <!-- Property Hero -->
    <section class="property-detail-hero">
        <div class="container">
            <h1>{{ $property->title }}</h1>
            <p class="location">{{ $property->address }}, {{ $property->city }}, {{ $property->province }} {{ $property->postal_code }}</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="property-detail-grid">
                <!-- Left: Main Content -->
                <div>
                    @php
                        $unitPhotos = is_array($activeUnit?->photo) ? $activeUnit->photo : (array)$activeUnit?->photo;
                        $allPhotos = array_filter($unitPhotos);
                        $firstPhoto = $allPhotos[0] ?? ($property->photo ? [$property->photo] : null);
                        $hasPhotos = !empty($allPhotos);
                    @endphp
                    <div class="property-main-image" id="gallery-main">
                        @if($hasPhotos)
                            <img src="{{ asset('storage/' . $allPhotos[0]) }}" alt="{{ $property->title }}" id="gallery-img">
                        @elseif($property->photo)
                            <img src="{{ asset('storage/' . $property->photo) }}" alt="{{ $property->title }}">
                        @else
                            <div class="placeholder">🏠</div>
                        @endif
                    </div>
                    @if($hasPhotos && count($allPhotos) > 1)
                    <div class="gallery-thumbs" id="gallery-thumbs">
                        @foreach($allPhotos as $index => $photo)
                            <button class="gallery-thumb {{ $index === 0 ? 'active' : '' }}" onclick="switchGalleryPhoto(this, '{{ asset('storage/' . $photo) }}')">
                                <img src="{{ asset('storage/' . $photo) }}" alt="Photo {{ $index + 1 }}">
                            </button>
                        @endforeach
                    </div>
                    @endif

                    @php
                        $allVideos = array_filter((array)($activeUnit?->videos ?? []));
                    @endphp
                    @if(!empty($allVideos))
                    <div class="property-videos">
                        <h3 style="font-size:1.2rem;color:#0F172A;margin:32px 0 16px;">Videos</h3>
                        <div class="video-grid">
                            @foreach($allVideos as $video)
                            <div class="video-item">
                                <video controls preload="metadata" style="width:100%;border-radius:8px;background:#000;">
                                    <source src="{{ asset('storage/' . $video) }}" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Specs -->
                    <div class="property-specs">
                        <div class="property-spec">
                            <div class="property-spec-value">{{ $activeUnit?->bedrooms ?? $property->bedrooms }}</div>
                            <div class="property-spec-label">Bedrooms</div>
                        </div>
                        <div class="property-spec">
                            <div class="property-spec-value">{{ $activeUnit?->bathrooms ?? $property->bathrooms }}</div>
                            <div class="property-spec-label">Bathrooms</div>
                        </div>
                        @if($property->square_feet)
                        <div class="property-spec">
                            <div class="property-spec-value">{{ number_format($property->square_feet) }}</div>
                            <div class="property-spec-label">Sq. Ft.</div>
                        </div>
                        @endif
                        <div class="property-spec">
                            <div class="property-spec-value">{{ $property->status === 'active' ? 'Rent' : ucfirst($property->status) }}</div>
                            <div class="property-spec-label">Listing Type</div>
                        </div>
                    </div>

                    @php
                        $unitAmenities = array_filter((array)($activeUnit?->amenities ?? []));
                        $propertyAmenities = array_filter((array)($property->tags ?? []));
                        $amenities = !empty($unitAmenities) ? $unitAmenities : $propertyAmenities;
                    @endphp
                    @if(!empty($amenities))
                    <!-- Amenities -->
                    <h2 style="font-size:1.4rem;color:#0F172A;margin:32px 0 20px;">Amenities</h2>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        @foreach($amenities as $amenity)
                            <span style="display:inline-flex;align-items:center;gap:6px;background:#F1F5F9;border:1px solid #E2E8F0;border-radius:999px;padding:8px 16px;font-size:0.9rem;color:#0F172A;">
                                ✅ {{ $amenity }}
                            </span>
                        @endforeach
                    </div>
                    @endif

                    <!-- Description -->
                    <h2 style="font-size:1.4rem;color:#0F172A;margin-bottom:20px;">Description</h2>
                    <div style="line-height:1.8;color:#4A4A4A;">
                        {!! ($activeUnit?->description ?? $property->description) !!}
                    </div>
                </div>

                <!-- Right: Sidebar -->
                <div>
                    <div class="property-sidebar">
                        <div class="property-sidebar-price">{{ $activeUnit?->rent_amount ? '$'.number_format($activeUnit->rent_amount).'/mo' : $property->formatted_price }}</div>
                        <div class="property-sidebar-label">{{ $property->property_type === 'for-rent' ? 'Per Month' : 'Listing Price' }}</div>

                        <div style="margin-bottom:20px;padding:16px 0;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                                <span style="color:#4A4A4A;">Status</span>
                                <span style="font-weight:600;color:#0F172A;">{{ $property->status === 'active' ? 'For Rent' : ucfirst($property->status) }}</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                                <span style="color:#4A4A4A;">City</span>
                                <span style="font-weight:600;color:#0F172A;">{{ $property->city }}</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;">
                                <span style="color:#4A4A4A;">Province</span>
                                <span style="font-weight:600;color:#0F172A;">{{ $property->province }}</span>
                            </div>
                        </div>

                        <a href="{{ route('rental-application.create', ['unit' => $activeUnit?->id]) }}" class="btn btn-gold" style="margin-bottom:10px;">Apply Now</a>
                        <a href="/contact" class="btn btn-outline" style="display:block;text-align:center;padding:12px;">Inquire About This Property</a>
                        <a href="/properties" class="btn btn-outline" style="display:block;text-align:center;padding:12px;margin-top:10px;">← Back to Properties</a>

                        <div style="margin-top:24px;padding:20px;background:#FFFFFF;border-radius:8px;">
                            <h4 style="font-family:'Manrope',sans-serif;font-weight:700;font-size:0.8rem;text-transform:uppercase;color:#0F172A;margin-bottom:12px;letter-spacing:0.06em;">Contact Us</h4>
                            <p style="font-size:0.9rem;color:#4A4A4A;margin-bottom:12px;">Have questions about this property?</p>
                            <a href="mailto:info@tenanttrust.ca" style="color:#DCA54A;font-weight:600;">info@tenanttrust.ca</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Similar Properties -->
    @if($similarProperties->count() > 0)
    <section class="section section-light">
        <div class="container">
            <div class="gold-accent"></div>
            <h2 class="section-title">Similar Properties</h2>
            <p class="section-subtitle">More listings in {{ $property->city }}</p>
            <div class="property-grid">
                @foreach($similarProperties as $similar)
                    <a href="{{ route('properties.show', $similar->slug) }}" class="property-card">
                        <div class="property-card-image">
                            @php
                                $similarActiveUnit = $similar->units->firstWhere("status", "active");
                                $similarPhotos = is_array($similarActiveUnit?->photo) ? $similarActiveUnit->photo : (array)$similarActiveUnit?->photo;
                                $similarFirstPhoto = $similarPhotos[0] ?? null;
                            @endphp
                            @if($similarFirstPhoto)
                                <img src="{{ asset('storage/' . $similarFirstPhoto) }}" alt="{{ $similar->title }}">
                            @elseif($similar->photo)
                                <img src="{{ asset('storage/' . $similar->photo) }}" alt="{{ $similar->title }}">
                            @else
                                <div class="placeholder">🏠</div>
                            @endif
                            <span class="property-card-badge">{{ $similar->status === 'active' ? 'For Rent' : ucfirst($similar->status) }}</span>
                        </div>
                        <div class="property-card-content">
                            <h3>{{ $similar->title }}</h3>
                            <p class="property-card-address">{{ $similar->address }}, {{ $similar->city }}</p>
                            <div class="property-card-details">
                                <span>🛏️ {{ $similar->units->firstWhere("status", "active")?->bedrooms ?? $similar->bedrooms }}</span>
                                <span>🚿 {{ $similar->units->firstWhere("status", "active")?->bathrooms ?? $similar->bathrooms }}</span>
                                @if($similar->square_feet)
                                    <span>📐 {{ number_format($similar->square_feet) }} sqft</span>
                                @endif
                            </div>
                            <div class="property-card-price">{{ $similar->units->firstWhere("status", "active")?->rent_amount ? '$'.number_format($similar->units->firstWhere("status", "active")->rent_amount).'/mo' : $similar->formatted_price }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif
@push('scripts')
<script>
function switchGalleryPhoto(thumb, src) {
    document.getElementById('gallery-img').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(function(t) {
        t.classList.remove('active');
    });
    thumb.classList.add('active');
}
</script>
@endpush
@endsection
