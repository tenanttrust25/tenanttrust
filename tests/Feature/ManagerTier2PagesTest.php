<?php

namespace Tests\Feature;

use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ManagerTier2PagesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // User is not a FilamentUser → Filament only permits panels when app.env is local.
        config(['app.env' => 'local']);
    }

    private function admin(): User
    {
        $admin = new User();
        $admin->name = 'Test Admin';
        $admin->email = 'test-admin-' . uniqid() . '@tenanttrust.ca';
        $admin->role = 'admin';
        $admin->password = bcrypt('secret');
        $admin->save();

        return $admin;
    }

    private function ticket(): MaintenanceTicket
    {
        $tenant = new User();
        $tenant->name = 'Tenant One';
        $tenant->email = 'tenant-' . uniqid() . '@example.com';
        $tenant->role = 'tenant';
        $tenant->password = bcrypt('secret');
        $tenant->save();

        $property = Property::create([
            'title' => 'Test Property',
            'slug' => 'test-property-' . uniqid(),
            'address' => '1 Test St',
            'city' => 'Sudbury',
            'province' => 'ON',
            'postal_code' => 'P3A 1A1',
        ]);

        return MaintenanceTicket::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'ticket_number' => 'TKT-TEST-' . uniqid(),
            'title' => 'Leaky faucet',
            'description' => 'Kitchen tap drips.',
            'status' => 'open',
            'priority' => 'medium',
        ]);
    }

    public function test_new_manager_pages_render(): void
    {
        $admin = $this->admin();

        foreach ([
            '/manager/vendors',
            '/manager/vendors/create',
            '/manager/compliance',
            '/manager/compliance/create',
            '/manager/reports',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_maintenance_forms_render_with_vendor_select(): void
    {
        $admin = $this->admin();
        $ticket = $this->ticket();

        $this->actingAs($admin)->get("/manager/maintenance/{$ticket->id}/edit")->assertOk();
        $this->actingAs($admin)->get("/admin/maintenance-tickets/{$ticket->id}/edit")->assertOk();
    }
}
