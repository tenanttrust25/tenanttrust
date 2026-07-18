<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Trust - Property Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-blue-600">Tenant Trust</a>
            <a href="/portals" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Portals</a>
        </div>
    </nav>

    <!-- Hero -->
    <div class="bg-blue-600 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-4">Find Your Next Rental</h1>
            <p class="text-xl">Browse available properties managed by Tenant Trust</p>
        </div>
    </div>

    <!-- Available Rentals -->
    <div class="max-w-7xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold mb-8">Available Rentals</h2>
        @if($properties->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($properties as $property)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                @if($property->images && count($property->images) > 0)
                <img src="{{ $property->images[0] }}" alt="{{ $property->title }}" class="w-full h-48 object-cover">
                @else
                <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-400">No Image</div>
                @endif
                <div class="p-4">
                    <h3 class="text-lg font-semibold">{{ $property->title }}</h3>
                    <p class="text-gray-600 mt-1">${{ number_format($property->price, 2) }}/mo</p>
                    <p class="text-gray-500 text-sm">{{ $property->address }}, {{ $property->city }}</p>
                    <div class="flex gap-4 mt-2 text-sm text-gray-500">
                        <span>{{ $property->bedrooms }} bed</span>
                        <span>{{ $property->bathrooms }} bath</span>
                        <span>{{ $property->square_feet }} sqft</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-8">{{ $properties->links() }}</div>
        @else
        <div class="text-center py-12 text-gray-500">
            <p class="text-xl">No properties currently available.</p>
            <p class="mt-2">Check back soon for new listings.</p>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-300 py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; {{ date('Y') }} Tenant Trust. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
