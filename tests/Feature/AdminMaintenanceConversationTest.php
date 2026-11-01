<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\MaintenanceTicketResource\Pages\EditMaintenanceTicket;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMaintenanceConversationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
    }

    private function admin(): User
    {
        $u = new User();
        $u->name = 'Test Admin';
        $u->email = 'tadm-' . uniqid() . '@tenanttrust.ca';
        $u->role = 'admin';
        $u->password = bcrypt('secret');
        $u->save();

        return $u;
    }

    private function tenant(): User
    {
        $t = new User();
        $t->name = 'Tenant Conv';
        $t->email = 'tconv-' . uniqid() . '@example.com';
        $t->role = 'tenant';
        $t->password = bcrypt('secret');
        $t->save();

        return $t;
    }

    private function ticket(User $tenant): MaintenanceTicket
    {
        $p = Property::create([
            'title' => 'Conv Prop',
            'slug' => 'conv-prop-' . uniqid(),
            'address' => '1 Conv St',
            'city' => 'Sudbury',
            'province' => 'ON',
            'postal_code' => 'P3A 1A1',
        ]);

        return MaintenanceTicket::create([
            'tenant_id' => $tenant->id,
            'property_id' => $p->id,
            'ticket_number' => 'TKT-TEST-' . uniqid(),
            'title' => 'Leaky tap',
            'description' => 'Kitchen tap drips.',
            'priority' => 'medium',
            'status' => 'open',
        ]);
    }

    public function test_admin_edit_page_renders_with_conversation_section(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $ticket = $this->ticket($tenant);
        $this->actingAs($admin);

        Livewire::test(EditMaintenanceTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertOk()
            ->assertFormExists()
            ->assertFormFieldExists('new_reply');
    }

    public function test_admin_form_reply_creates_ticket_reply(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $ticket = $this->ticket($tenant);
        $this->actingAs($admin);

        Livewire::test(EditMaintenanceTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['new_reply' => 'Technician booked for Thursday.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ticket_replies', [
            'maintenance_ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Technician booked for Thursday.',
        ]);
    }

    public function test_admin_save_and_reply_header_action_creates_ticket_reply(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $ticket = $this->ticket($tenant);
        $this->actingAs($admin);

        Livewire::test(EditMaintenanceTicket::class, ['record' => $ticket->getRouteKey()])
            ->callAction('save_and_reply', ['reply_message' => 'Parts have been ordered.'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('ticket_replies', [
            'maintenance_ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Parts have been ordered.',
        ]);
    }

    public function test_conversation_thread_shows_existing_reply(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $ticket = $this->ticket($tenant);
        $this->actingAs($admin);

        TicketReply::create([
            'maintenance_ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Existing thread message here.',
        ]);

        Livewire::test(EditMaintenanceTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertOk()
            ->assertSee('Existing thread message here.');
    }
}
