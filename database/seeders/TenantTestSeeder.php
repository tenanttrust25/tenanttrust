<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Property;
use App\Models\TenantFile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantTestSeeder extends Seeder
{
    public function run(): void
    {
        // Add role to admin
        User::where("email", "admin@tenanttrust.ca")->update(["role" => "admin"]);

        // Create tenant user
        $tenant = User::create([
            "name" => "Sarah Tenant",
            "email" => "sarah@example.com",
            "password" => bcrypt("password"),
            "role" => "tenant",
            "phone" => "555-0101",
            "address" => "123 Main Street",
            "mailing_address" => "123 Main Street, Toronto, ON M5A 1A1",
        ]);

        // Create properties
        $prop1 = Property::create([
            "title" => "123 Main Street",
            "slug" => "123-main-street",
            "description" => "Beautiful 2-bedroom apartment in downtown Toronto.",
            "price" => 2500,
            "address" => "123 Main St",
            "city" => "Toronto",
            "province" => "ON",
            "postal_code" => "M5A 1A1",
            "bedrooms" => 2,
            "bathrooms" => 1,
            "property_type" => "for-rent",
            "status" => "active",
            "featured" => true,
        ]);

        $prop2 = Property::create([
            "title" => "456 Oak Avenue",
            "slug" => "456-oak-avenue",
            "description" => "Spacious 3-bedroom townhouse with parking.",
            "price" => 3200,
            "address" => "456 Oak Ave",
            "city" => "Toronto",
            "province" => "ON",
            "postal_code" => "M5B 2B2",
            "bedrooms" => 3,
            "bathrooms" => 2,
            "property_type" => "for-rent",
            "status" => "active",
            "featured" => true,
        ]);

        // Create invoices
        $inv1 = Invoice::create([
            "tenant_id" => $tenant->id,
            "property_id" => $prop1->id,
            "invoice_number" => "INV-2026-001",
            "amount" => 2500.00,
            "description" => "Monthly Rent - June 2026",
            "due_date" => "2026-06-01",
            "status" => "paid",
        ]);

        Invoice::create([
            "tenant_id" => $tenant->id,
            "property_id" => $prop1->id,
            "invoice_number" => "INV-2026-002",
            "amount" => 2500.00,
            "description" => "Monthly Rent - July 2026",
            "due_date" => "2026-07-01",
            "status" => "pending",
        ]);

        // Create payment for inv1
        Payment::create([
            "invoice_id" => $inv1->id,
            "tenant_id" => $tenant->id,
            "amount" => 2500.00,
            "payment_method" => "interac",
            "transaction_id" => "ETR-" . strtoupper(Str::random(10)),
            "status" => "completed",
            "paid_at" => now(),
        ]);

        // Create tenant files
        TenantFile::create([
            "tenant_id" => $tenant->id,
            "uploaded_by" => 1,
            "name" => "Signed Lease Agreement",
            "description" => "Your signed lease for 123 Main Street - valid until June 2027",
            "file_path" => "sample.pdf",
            "file_type" => "lease",
        ]);

        TenantFile::create([
            "tenant_id" => $tenant->id,
            "uploaded_by" => 1,
            "name" => "N1 Rent Increase Notice",
            "description" => "Notice of rent increase effective August 1, 2026",
            "file_path" => "sample.pdf",
            "file_type" => "N1",
        ]);

        // Create maintenance tickets
        MaintenanceTicket::create([
            "tenant_id" => $tenant->id,
            "property_id" => $prop1->id,
            "ticket_number" => "TKT-A1B2C3D4",
            "title" => "Leaky faucet in kitchen",
            "description" => "The kitchen faucet has been dripping for 3 days. Water is pooling on the counter.",
            "priority" => "medium",
            "status" => "in_progress",
        ]);

        MaintenanceTicket::create([
            "tenant_id" => $tenant->id,
            "property_id" => $prop2->id,
            "ticket_number" => "TKT-E5F6G7H8",
            "title" => "Broken garbage disposal",
            "description" => "The garbage disposal stopped working and makes a loud humming noise when switched on.",
            "priority" => "high",
            "status" => "open",
        ]);

        echo "Test data seeded!\n";
    }
}
