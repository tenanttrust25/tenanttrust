@extends('layouts.app')

@section('title', 'Properties - Tenant Trust')

@section('content')
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Properties</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <!-- Filters -->
            <form method="GET" action="{{ route('properties.index') }}" class="filters-bar">
                <select name="property_type">
                    <option value="">All Types</option>
                    <option value="for-rent" @if(request('property_type') === 'for-rent') selected @endif>For Rent</option>
                    <option value="for-sale" @if(request('property_type') === 'for-sale') selected @endif>For Sale</option>
                </select>
                <select name="city">
                    <option value="">All Cities</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}" @if(request('city') === $city) selected @endif>{{ $city }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-gold">Filter</button>
                @if(request()->anyFilled(['property_type', 'city']))
                    <a href="{{ route('properties.index') }}" class="btn btn-outline">Clear</a>
                @endif
            </form>

            @if($activeUnits->count() > 0)
                <div class="property-grid">
                    @foreach($activeUnits as $unit)
                        @php $property = $unit->property; @endphp
                        <a href="{{ route('properties.show', $unit->slug ?? $property->slug) }}" class="property-card">
                            <div class="property-card-image">
                                @php
                                    $unitPhotos = is_array($unit->photo) ? $unit->photo : (array)$unit->photo;
                                    $firstPhoto = $unitPhotos[0] ?? null;
                                @endphp
                                @if($firstPhoto)
                                    <img src="{{ asset('storage/' . $firstPhoto) }}" alt="{{ $property->title }}">
                                @elseif($property->photo)
                                    <img src="{{ asset('storage/' . $property->photo) }}" alt="{{ $property->title }}">
                                @else
                                    <div class="placeholder">🏠</div>
                                @endif
                                <span class="property-card-badge">For Rent</span>
                            </div>
                            <div class="property-card-content">
                                <h3>{{ $property->title }}{{ $unit->unit ? ' - ' . $unit->unit : '' }}</h3>
                                <p class="property-card-address">{{ $property->address }}, {{ $property->city }}, {{ $property->province }}</p>
                                <div class="property-card-details">
                                    <span>🛏️ {{ $unit->bedrooms ?? $property->bedrooms }}</span>
                                    <span>🚿 {{ $unit->bathrooms ?? $property->bathrooms }}</span>
                                    @if($unit->square_feet || $property->square_feet)
                                        <span>📐 {{ number_format($unit->square_feet ?? $property->square_feet) }} sqft</span>
                                    @endif
                                </div>
                                <div class="property-card-price">{{ $unit->rent_amount ? '$'.number_format($unit->rent_amount).'/mo' : $property->formatted_price }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <p>No properties currently available.</p>
                    <p>Check back soon for new listings.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
