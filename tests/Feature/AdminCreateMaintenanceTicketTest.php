<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\MaintenanceTicketResource\Pages\CreateMaintenanceTicket;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\User;
use App\Notifications\TenantMaintenanceTicketCreatedNotification;
use App\Notifications\OwnerMaintenanceTicketCreatedNotification;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCreateMaintenanceTicketTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_ticket_and_tenant_is_notified(): void
    {
        Notification::fake();
        $this->mock(PushNotificationService::class, function ($mock): void {
            $mock->shouldReceive("sendToUser")->andReturn(0);
        });

        $admin = User::create([
            "name" => "Test Admin",
            "email" => "admin-" . uniqid() . "@tenanttrust.ca",
            "role" => "admin",
            "password" => "secret",
        ]);

        $tenant = User::create([
            "name" => "Test Tenant",
            "email" => "tenant-" . uniqid() . "@example.com",
            "role" => "tenant",
            "password" => "secret",
        ]);

        $owner = User::create([
            "name" => "Test Owner",
            "email" => "owner-" . uniqid() . "@tenanttrust.ca",
            "role" => "owner",
            "password" => "secret",
        ]);

        $property = Property::create([
            "title" => "Test Property",
            "slug" => "test-property-" . uniqid(),
            "address" => "1 Test St",
            "city" => "Sudbury",
            "province" => "ON",
            "postal_code" => "P3A 1A1",
            "owner_id" => $owner->id,
        ]);

        $unit = PropertyUnit::create([
            "property_id" => $property->id,
            "unit" => "1",
            "rent_amount" => 1000,
            "status" => "occupied",
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateMaintenanceTicket::class)
            ->fillForm([
                "tenant_id" => $tenant->id,
                "property_id" => $property->id,
                "property_unit_id" => $unit->id,
                "title" => "Test admin ticket",
                "description" => "Created from the admin panel.",
                "category" => "plumbing",
                "priority" => "medium",
            ])
            ->call("create")
            ->assertHasNoFormErrors();

        $ticket = MaintenanceTicket::where("title", "Test admin ticket")->first();

        $this->assertNotNull($ticket);
        $this->assertSame($tenant->id, $ticket->tenant_id);
        $this->assertSame($property->id, $ticket->property_id);
        $this->assertSame($unit->id, $ticket->property_unit_id);
        $this->assertSame("open", $ticket->status);
        $this->assertNotEmpty($ticket->ticket_number);
        $this->assertStringStartsWith("TKT-", $ticket->ticket_number);

        Notification::assertSentTo($tenant, TenantMaintenanceTicketCreatedNotification::class);
        Notification::assertSentTo($owner, OwnerMaintenanceTicketCreatedNotification::class);
    }
}
