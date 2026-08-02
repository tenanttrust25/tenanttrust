<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use App\Models\Page;
use App\Models\Property;
use App\Models\PropertyUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function home()
    {
        $featuredProperties = Property::active()->with("units")->featured()->latest()->take(6)->get();
        $recentProperties = Property::active()->with("units")->latest()->take(3)->get();
        return view('home', compact('featuredProperties', 'recentProperties'));
    }

    public function properties(Request $request)
    {
        $query = Property::active()->with("units");

        if ($request->filled('property_type')) {
            $query->where('property_type', $request->property_type);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        $activeUnits = PropertyUnit::with("property")->where("status", "active")->latest()->paginate(12)->withQueryString();
        $properties = $query->latest()->paginate(12)->withQueryString();
        $cities = Property::active()->with("units")->select('city')->distinct()->pluck('city');

        return view('properties.index', compact('activeUnits', 'properties', 'cities'));
    }

    public function propertyDetail($slug)
    {
        $unit = \App\Models\PropertyUnit::with('property')->where('slug', $slug)->first();
        $property = $unit ? $unit->property : Property::where('slug', $slug)->active()->with('units')->firstOrFail();
        $activeUnit = $unit ?? $property->units->firstWhere('status', 'active');
        $similarProperties = Property::active()->with("units")
            ->where('city', $property->city)
            ->where('id', '!=', $property->id)
            ->whereHas('units', fn ($q) => $q->where('status', 'active'))
            ->take(3)
            ->get();
        return view('properties.show', compact('property', 'activeUnit', 'similarProperties'));
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }

    public function contactSubmit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|max:20',
            'message' => 'required',
        ]);

        $submission = ContactSubmission::create($validated);

        // Notify admin
        try {
            \Illuminate\Support\Facades\Mail::mailer("sendmail")
                ->to("info@tenanttrust.ca")
                ->send(new \App\Mail\ContactSubmissionMail($submission));
        } catch (\Exception $e) {
            // Email failure shouldn't block the user's submission
        }

        return back()->with('success', 'Thank you! Your message has been received. We will get back to you shortly.');
    }

    public function page($slug)
    {
        $page = Page::where('slug', $slug)->published()->firstOrFail();
        return view('page', compact('page'));
    }

    public function portals()
    {
        return view("portals");
    }
}
