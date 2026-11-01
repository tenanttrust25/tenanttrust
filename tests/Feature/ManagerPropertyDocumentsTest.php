<?php

namespace Tests\Feature;

use App\Filament\Manager\Resources\PropertyResource\Pages\EditProperty;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerPropertyDocumentsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
    }

    public function test_documents_repeater_item_renders(): void
    {
        $admin = new User();
        $admin->name = 'Test Admin';
        $admin->email = 'ta-' . uniqid() . '@tenanttrust.ca';
        $admin->role = 'admin';
        $admin->password = bcrypt('secret');
        $admin->save();

        $tenant = new User();
        $tenant->name = 'Tenant Doc';
        $tenant->email = 'td-' . uniqid() . '@example.com';
        $tenant->role = 'tenant';
        $tenant->password = bcrypt('secret');
        $tenant->save();

        $property = Property::create([
            'title' => 'Doc Prop',
            'slug' => 'doc-prop-' . uniqid(),
            'address' => '1 A St',
            'city' => 'Sudbury',
            'province' => 'ON',
            'postal_code' => 'P3A1A1',
        ]);

        $this->actingAs($admin);

        // Filling the upload repeater with an item forces the itemLabel closure
        // to evaluate on render — this is what threw 500 when clicking "Upload Document".
        Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
            ->fillForm([
                'tenantFilesUpload' => [
                    ['name' => 'Signed Lease', 'tenant_id' => $tenant->id, 'file_type' => 'lease'],
                ],
            ])
            ->assertOk();
    }
}
