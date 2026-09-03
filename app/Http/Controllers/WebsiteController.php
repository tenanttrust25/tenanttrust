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
        $captcha = $this->generateCaptcha();

        return view('contact', compact('captcha'));
    }

    /**
     * Generate a fresh arithmetic CAPTCHA question (word-form, so naive
     * scrapers can't regex the operands) and stash the answer in the session.
     */
    private function generateCaptcha(): array
    {
        $words = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'];
        $plus = (bool) random_int(0, 1);

        if ($plus) {
            $a = random_int(1, 10);
            $b = random_int(1, 10);
            $answer = $a + $b;
            $op = 'plus';
        } else {
            $a = random_int(2, 10);
            $b = random_int(1, $a - 1);
            $answer = $a - $b;
            $op = 'minus';
        }

        session(['contact_captcha_answer' => (string) $answer]);

        return ['question' => "{$words[$a - 1]} {$op} {$words[$b - 1]}"];
    }

    public function contactSubmit(Request $request)
    {
        // Honeypot trap: real humans never see the hidden "company_website"
        // field, so if it's filled this is a bot. Pretend success, store nothing.
        // Same for submissions spoofing Tenant Trust's own domain
        // (e.g. info@tenanttrust.ca) — real visitors never email us from
        // our own domain, only spammers do.
        if ($request->filled('company_website') || \App\Services\EmailDomainGuard::isBlocked($request->input('email'))) {
            return back()->with('success', 'Thank you! Your message has been received. We will get back to you shortly.');
        }

        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|max:20',
            'message' => 'required',
            'captcha' => ['required', function ($attribute, $value, $fail) {
                $expected = session('contact_captcha_answer');
                session()->forget('contact_captcha_answer'); // single-use
                if ($expected === null || (string) $value === '' || (int) $value !== (int) $expected) {
                    $fail('The security check answer is incorrect. Please try again.');
                }
            }],
        ]);
        unset($validated['captcha']);

        $submission = ContactSubmission::create($validated);

        // Notify admin
        try {
            \Illuminate\Support\Facades\Mail::mailer("smtp")
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
