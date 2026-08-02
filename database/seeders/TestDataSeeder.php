<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Property;
use App\Models\TenantFile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create the admin user if not exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@tenanttrust.ca'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => null,
                'address' => null,
            ]
        );

        // Create tenant user
        $tenant = User::firstOrCreate(
            ['email' => 'tenant@example.com'],
            [
                'name' => 'John Tenant',
                'password' => Hash::make('password123'),
                'role' => 'tenant',
                'phone' => '555-1234',
                'address' => '789 Tenant Lane, Suite 100',
            ]
        );

        // Create properties
        $property1 = Property::firstOrCreate(
            ['slug' => '123-main-st'],
            [
                'title' => '123 Main St',
                'slug' => '123-main-st',
                'description' => 'A beautiful downtown apartment with modern finishes.',
                'price' => 1800.00,
                'address' => '123 Main Street',
                'city' => 'Toronto',
                'province' => 'Ontario',
                'postal_code' => 'M5V 2T6',
                'bedrooms' => 2,
                'bathrooms' => 1,
                'square_feet' => 800,
                'property_type' => 'for-rent',
                'status' => 'active',
                'featured' => true,
            ]
        );

        $property2 = Property::firstOrCreate(
            ['slug' => '456-oak-ave'],
            [
                'title' => '456 Oak Ave',
                'slug' => '456-oak-ave',
                'description' => 'Spacious family home in a quiet neighborhood.',
                'price' => 2500.00,
                'address' => '456 Oak Avenue',
                'city' => 'Toronto',
                'province' => 'Ontario',
                'postal_code' => 'M4B 1C3',
                'bedrooms' => 3,
                'bathrooms' => 2,
                'square_feet' => 1200,
                'property_type' => 'for-rent',
                'status' => 'active',
                'featured' => false,
            ]
        );

        // Create invoices
        $invoice1 = Invoice::firstOrCreate(
            ['invoice_number' => 'INV-2026-0001'],
            [
                'tenant_id' => $tenant->id,
                'property_id' => $property1->id,
                'invoice_number' => 'INV-2026-0001',
                'amount' => 1800.00,
                'description' => 'Monthly rent for June 2026 - 123 Main St',
                'due_date' => '2026-06-01',
                'status' => 'paid',
                'file_path' => null,
            ]
        );

        $invoice2 = Invoice::firstOrCreate(
            ['invoice_number' => 'INV-2026-0002'],
            [
                'tenant_id' => $tenant->id,
                'property_id' => $property1->id,
                'invoice_number' => 'INV-2026-0002',
                'amount' => 1800.00,
                'description' => 'Monthly rent for July 2026 - 123 Main St',
                'due_date' => '2026-07-01',
                'status' => 'pending',
                'file_path' => null,
            ]
        );

        $invoice3 = Invoice::firstOrCreate(
            ['invoice_number' => 'INV-2026-0003'],
            [
                'tenant_id' => $tenant->id,
                'property_id' => $property1->id,
                'invoice_number' => 'INV-2026-0003',
                'amount' => 1800.00,
                'description' => 'Monthly rent for May 2026 - 123 Main St (Late Payment)',
                'due_date' => '2026-05-01',
                'status' => 'overdue',
                'file_path' => null,
            ]
        );

        // Create payments
        Payment::firstOrCreate(
            ['invoice_id' => $invoice1->id, 'transaction_id' => 'TXN-INT-001'],
            [
                'invoice_id' => $invoice1->id,
                'tenant_id' => $tenant->id,
                'amount' => 1800.00,
                'payment_method' => 'interac',
                'transaction_id' => 'TXN-INT-001',
                'status' => 'completed',
                'paid_at' => now()->subDays(5),
            ]
        );

        Payment::firstOrCreate(
            ['invoice_id' => $invoice3->id, 'transaction_id' => 'TXN-CC-001'],
            [
                'invoice_id' => $invoice3->id,
                'tenant_id' => $tenant->id,
                'amount' => 900.00,
                'payment_method' => 'credit',
                'transaction_id' => 'TXN-CC-001',
                'status' => 'pending',
                'paid_at' => null,
            ]
        );

        // Create tenant files
        TenantFile::firstOrCreate(
            ['name' => 'Lease Agreement - 123 Main St', 'tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'uploaded_by' => $admin->id,
                'name' => 'Lease Agreement - 123 Main St',
                'description' => 'Signed lease agreement for the period June 2026 - May 2027',
                'file_path' => 'tenant-files/lease-123-main.pdf',
                'file_type' => 'lease',
            ]
        );

        TenantFile::firstOrCreate(
            ['name' => 'N1 Form - Rent Increase Notice', 'tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'uploaded_by' => $admin->id,
                'name' => 'N1 Form - Rent Increase Notice',
                'description' => 'Notice of rent increase effective August 2026',
                'file_path' => 'tenant-files/n1-increase.pdf',
                'file_type' => 'N1',
            ]
        );

        // Create maintenance tickets
        MaintenanceTicket::firstOrCreate(
            ['ticket_number' => 'TKT-202606-0001'],
            [
                'tenant_id' => $tenant->id,
                'property_id' => $property1->id,
                'ticket_number' => 'TKT-202606-0001',
                'title' => 'Leaky kitchen faucet',
                'description' => 'The kitchen faucet has been dripping constantly for the past few days. Water is pooling around the base.',
                'priority' => 'medium',
                'status' => 'open',
                'photos' => null,
            ]
        );

        MaintenanceTicket::firstOrCreate(
            ['ticket_number' => 'TKT-202606-0002'],
            [
                'tenant_id' => $tenant->id,
                'property_id' => $property1->id,
                'ticket_number' => 'TKT-202606-0002',
                'title' => 'Heating not working in bedroom',
                'description' => 'The radiator in the master bedroom is not producing heat. This has been an issue since last week.',
                'priority' => 'high',
                'status' => 'in_progress',
                'photos' => null,
            ]
        );
    }
}
