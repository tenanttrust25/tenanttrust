<?php

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\RentalApplicationController;
use App\Http\Controllers\CloverPaymentController;
use App\Http\Controllers\StripePaymentController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get("/", [WebsiteController::class, "home"])->name("home");
use App\Http\Controllers\InstallController;

// Installation wizard
Route::get("/install", [InstallController::class, "index"])->name("install");
Route::post("/install/run", [InstallController::class, "run"])->name("install.run");
Route::get("/properties", [WebsiteController::class, "properties"])->name("properties.index");
Route::get("/properties/{slug}", [WebsiteController::class, "propertyDetail"])->name("properties.show");
Route::get("/about", [WebsiteController::class, "about"])->name("about");
Route::get("/apply", [RentalApplicationController::class, "create"])->name("rental-application.create");
Route::post("/apply", [RentalApplicationController::class, "store"])->name("rental-application.store");
Route::get("/apply/thank-you", [RentalApplicationController::class, "thankYou"])->name("rental-application.thank-you");
Route::get("/contact", [WebsiteController::class, "contact"])->name("contact");
Route::post("/contact", [WebsiteController::class, "contactSubmit"])->name("contact.submit");
Route::get("/portals", [WebsiteController::class, "portals"])->name("portals");

// Stripe payment processing
Route::post("/api/stripe/create-intent", [StripePaymentController::class, "createPaymentIntent"])
    ->middleware(["web", "auth"])
    ->name("stripe.create-intent");

Route::post("/api/stripe/confirm", [StripePaymentController::class, "confirmPayment"])
    ->middleware(["web", "auth"])
    ->name("stripe.confirm");

// Mail campaign tracking
Route::get("/mail/track/open/{hash}", [App\Http\Controllers\MailTrackingController::class, "trackOpen"])->name("mail.track.open");
Route::get("/mail/track/click/{hash}", [App\Http\Controllers\MailTrackingController::class, "trackClick"])->name("mail.track.click");
Route::get("/mail/unsubscribe/{hash}", [App\Http\Controllers\MailTrackingController::class, "unsubscribe"])->name("mail.track.unsubscribe");

// Year-end rent receipt download (authenticated)
Route::get("/tenant/year-end/receipt", function () {
    if (!auth()->check()) {
        return redirect("/tenant/login");
    }
    
    $user = auth()->user();
    $tenantIds = $user->getUnitTenantIds();
    $year = (int) request("year", now()->year);
    
    $paidInvoices = App\Models\Invoice::with(["property", "propertyUnit"])
        ->whereIn("tenant_id", $tenantIds)
        ->where("status", "paid")
        ->whereYear("due_date", $year)
        ->orderBy("due_date")
        ->get();
    
    $totalPaid = $paidInvoices->sum("amount");
    
    // Get the first invoice's property/unit for the address
    $firstInvoice = $paidInvoices->first();
    $unitAddress = "";
    if ($firstInvoice && $firstInvoice->property) {
        $p = $firstInvoice->property;
        $unit = $firstInvoice->propertyUnit?->unit ?? "";
        $unitAddress = ($unit ? "Unit {$unit}, " : "") . $p->address . ", " . $p->city . ", " . $p->province . " " . $p->postal_code;
    }
    
    // Business contact info from system
    $businessAddress = "3-474 Mckim St, Sudbury, ON P3C 2L7";
    $businessPhone = "249-523-3331";
    $businessEmail = "info@tenanttrust.ca";
    $businessWebsite = "tenanttrust.ca";
    
    $pdf = Barryvdh\DomPDF\Facade\Pdf::loadView("pdfs.rent-receipt", [
        "user" => $user,
        "year" => $year,
        "paidInvoices" => $paidInvoices,
        "totalPaid" => $totalPaid,
        "unitAddress" => $unitAddress,
        "businessAddress" => $businessAddress,
        "businessPhone" => $businessPhone,
        "businessEmail" => $businessEmail,
        "businessWebsite" => $businessWebsite,
    ]);
    
    return response($pdf->output(), 200, [
        "Content-Type" => "application/pdf",
        "Content-Disposition" => "attachment; filename=Rent_Receipt_$year.pdf",
    ]);
})->name("tenant.year-end.receipt");

// Dynamic pages from database - must be LAST
Route::get("/{slug}", [WebsiteController::class, "page"])->name("page");

// Stripe Connect webhook (CSRF exempt in bootstrap/app.php)
Route::post('api/stripe/connect/webhook', [\App\Http\Controllers\StripeConnectWebhookController::class, 'handleWebhook']);

// Push notification subscriptions (authenticated — controller returns 401 JSON for guests)
Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'subscribe'])
    ->middleware('web')
    ->name('push.subscribe');
Route::post('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe'])
    ->middleware('web')
    ->name('push.unsubscribe');

// PWA: service workers (scoped to each panel path) + web app manifests
Route::get('/tenant/sw.js', fn () => response(file_get_contents(public_path('pwa/sw.js')))
    ->header('Content-Type', 'application/javascript')
    ->header('Cache-Control', 'no-cache'));
Route::get('/owner/sw.js', fn () => response(file_get_contents(public_path('pwa/sw.js')))
    ->header('Content-Type', 'application/javascript')
    ->header('Cache-Control', 'no-cache'));

Route::get('/pwa/tenant-manifest.webmanifest', function () {
    $manifest = [
        'name' => 'Tenant Trust — Tenant Portal',
        'short_name' => 'Tenant Portal',
        'description' => 'Pay rent, submit maintenance requests, and manage your tenancy from your phone.',
        'start_url' => '/tenant',
        'scope' => '/tenant',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#0F172A',
        'theme_color' => '#DCA54A',
        'icons' => [
            ['src' => '/pwa/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/pwa/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => '/pwa/icons/icon-maskable-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
            ['src' => '/pwa/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ];
    return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
});

Route::get('/pwa/owner-manifest.webmanifest', function () {
    $manifest = [
        'name' => 'Tenant Trust — Client Portal',
        'short_name' => 'Client Portal',
        'description' => 'Manage your properties, maintenance, and payouts from your phone.',
        'start_url' => '/owner',
        'scope' => '/owner',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#0F172A',
        'theme_color' => '#DCA54A',
        'icons' => [
            ['src' => '/pwa/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/pwa/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => '/pwa/icons/icon-maskable-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
            ['src' => '/pwa/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ];
    return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
});
