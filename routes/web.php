<?php

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\RentalApplicationController;
use App\Http\Controllers\CloverPaymentController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\SecureFileController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get("/", [WebsiteController::class, "home"])->name("home");
// Installation wizard REMOVED 2026-11-03: the POST handler had no "already installed"
// guard and could create a role=admin user on the live site. Re-enable only behind
// an artisan command, never as a public route.

Route::get("/properties", [WebsiteController::class, "properties"])->name("properties.index");
Route::get("/properties/{slug}", [WebsiteController::class, "propertyDetail"])->name("properties.show");
Route::get("/properties/{slug}/inquire", [WebsiteController::class, "propertyInquire"])->name("properties.inquire");
Route::get("/about", [WebsiteController::class, "about"])->name("about");
Route::get("/apply", [RentalApplicationController::class, "create"])->name("rental-application.create");
Route::post("/apply", [RentalApplicationController::class, "store"])->name("rental-application.store");
Route::get("/apply/thank-you", [RentalApplicationController::class, "thankYou"])->name("rental-application.thank-you");
Route::get("/contact", [WebsiteController::class, "contact"])->name("contact");
Route::post("/contact", [WebsiteController::class, "contactSubmit"])->middleware("throttle:10,1")->name("contact.submit");
Route::get("/portals", [WebsiteController::class, "portals"])->name("portals");

// Twilio voice handling (inbound calls)
Route::post("/twilio/voice", [App\Http\Controllers\TwilioVoiceController::class, "voice"])
    ->middleware("twilio.signature")
    ->name("twilio.voice");
Route::post("/twilio/gather", [App\Http\Controllers\TwilioVoiceController::class, "gather"])
    ->middleware("twilio.signature")
    ->name("twilio.gather");
Route::post("/twilio/think", [App\Http\Controllers\TwilioVoiceController::class, "think"])
    ->middleware("twilio.signature")
    ->name("twilio.think");
Route::post("/twilio/maintenance", [App\Http\Controllers\TwilioVoiceController::class, "maintenanceIssue"])
    ->middleware("twilio.signature")
    ->name("twilio.maintenance");
Route::post("/twilio/escalate", [App\Http\Controllers\TwilioVoiceController::class, "escalate"])
    ->middleware("twilio.signature")
    ->name("twilio.escalate");
Route::post("/twilio/dial-result", [App\Http\Controllers\TwilioVoiceController::class, "dialResult"])
    ->middleware("twilio.signature")
    ->name("twilio.dial-result");
Route::post("/twilio/whisper", [App\Http\Controllers\TwilioVoiceController::class, "whisper"])
    ->middleware("twilio.signature")
    ->name("twilio.whisper");
Route::post("/twilio/askname", [App\Http\Controllers\TwilioVoiceController::class, "askName"])
    ->middleware("twilio.signature")
    ->name("twilio.askname");
Route::post("/twilio/record", [App\Http\Controllers\TwilioVoiceController::class, "record"])
    ->middleware("twilio.signature")
    ->name("twilio.record");

// Inbound SMS on the business number -> forwarded to Steve's mobile.
Route::post("/twilio/sms", [App\Http\Controllers\TwilioSmsForwardController::class, "incoming"])
    ->middleware("twilio.signature")
    ->name("twilio.sms");

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

// Client invoice payments (public, tokenized) — must be BEFORE the catch-all slug route
Route::get("/invoice/pay/{token}", [App\Http\Controllers\OwnerInvoicePaymentController::class, "show"])->name("invoice.pay.show");
Route::post("/invoice/pay/{token}/checkout", [App\Http\Controllers\OwnerInvoicePaymentController::class, "checkout"])->name("invoice.pay.checkout");
Route::get("/invoice/pay/{token}/success", [App\Http\Controllers\OwnerInvoicePaymentController::class, "success"])->name("invoice.pay.success");
Route::get("/invoice/pay/{token}/cancel", [App\Http\Controllers\OwnerInvoicePaymentController::class, "cancel"])->name("invoice.pay.cancel");

// Authenticated file delivery — the ONLY way a tenant/owner document can be fetched
// since /storage/ is denied at nginx. Every route checks the viewer.
Route::prefix("files")->name("files.")->group(function () {
    Route::get("tenant-file/{tenantFile}", [SecureFileController::class, "tenantFile"])->name("tenant-file");
    Route::get("owner-file/{ownerFile}", [SecureFileController::class, "ownerFile"])->name("owner-file");
    Route::get("n9/{n9Submission}", [SecureFileController::class, "n9"])->name("n9");
    Route::get("ticket/{ticket}/invoice", [SecureFileController::class, "ticketInvoice"])->name("ticket.invoice");
    Route::get("ticket/{ticket}/photo/{index}", [SecureFileController::class, "ticketPhoto"])->name("ticket.photo");
    Route::get("invoice/{invoice}", [SecureFileController::class, "invoice"])->name("invoice");
    Route::get("owner-invoice/{ownerInvoice}", [SecureFileController::class, "owner-invoice"])->name("owner-invoice");
    Route::get("form/{form}", [SecureFileController::class, "form"])->name("form");
    Route::get("signed/{encoded}", [SecureFileController::class, "signed"])->name("signed")->middleware("signed");
    Route::get("form-submission/{formSubmission}", [SecureFileController::class, "formSubmission"])->name("form-submission");
    Route::get("mail-item/{mailItem}", [SecureFileController::class, "mail-item"])->name("mail-item");
    Route::get("application/{rentalApplication}/{field}", [SecureFileController::class, "application"])->name("application");
});

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
Route::get('/admin/sw.js', fn () => response(file_get_contents(public_path('pwa/sw.js')))
    ->header('Content-Type', 'application/javascript')
    ->header('Cache-Control', 'no-cache'));
Route::get('/manager/sw.js', fn () => response(file_get_contents(public_path('pwa/sw.js')))
    ->header('Content-Type', 'application/javascript')
    ->header('Cache-Control', 'no-cache'));

Route::get('/pwa/admin-manifest.webmanifest', function () {
    $manifest = [
        'name' => 'Tenant Trust — Admin',
        'short_name' => 'Admin',
        'description' => 'Tenant Trust administration — properties, people, billing and operations.',
        'start_url' => '/admin',
        'scope' => '/admin',
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

Route::get('/pwa/manager-manifest.webmanifest', function () {
    $manifest = [
        'name' => 'Tenant Trust — Manager',
        'short_name' => 'Manager',
        'description' => 'Property Manager Portal — maintenance, leasing and rent operations.',
        'start_url' => '/manager',
        'scope' => '/manager',
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
