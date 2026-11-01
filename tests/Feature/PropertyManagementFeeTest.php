<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\PropertyResource\Pages\EditProperty;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PropertyManagementFeeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    }

    private function makeProperty(?string $feeType, ?float $feeValue, float $rent): array
    {
        $p = Property::create([
            'title' => 'Test Prop '.uniqid(),
            'slug' => 'test-prop-'.uniqid(),
            'address' => '1 Test St',
            'city' => 'Sudbury',
            'province' => 'ON',
            'postal_code' => 'P3A1A1',
            'management_fee_type' => $feeType,
            'management_fee_value' => $feeValue,
        ]);
        $u = $p->units()->create([
            'unit' => 'Unit 1',
            'rent_amount' => $rent,
        ]);

        return [$p, $u];
    }

    public function test_percentage_property_autofills_fee_at_property_rate(): void
    {
        [$p, $u] = $this->makeProperty('percentage', 6.10, 1000);
        $key = 'record-'.$u->id;

        Livewire::test(EditProperty::class, ['record' => $p->getKey()])
            ->set("data.units.{$key}.rent_amount", 2000)
            ->assertSet("data.units.{$key}.monthly_management_fee", fn ($v) => (float) $v === 122.0);
    }

    public function test_null_type_property_autofills_default_61_percent(): void
    {
        [$p, $u] = $this->makeProperty(null, null, 1000);
        $key = 'record-'.$u->id;

        Livewire::test(EditProperty::class, ['record' => $p->getKey()])
            ->set("data.units.{$key}.rent_amount", 1500)
            ->assertSet("data.units.{$key}.monthly_management_fee", fn ($v) => (float) $v === 91.5);
    }

    public function test_flat_property_keeps_fixed_fee(): void
    {
        [$p, $u] = $this->makeProperty('flat', 60.00, 1000);
        $key = 'record-'.$u->id;

        Livewire::test(EditProperty::class, ['record' => $p->getKey()])
            ->set("data.units.{$key}.rent_amount", 2000)
            ->assertSet("data.units.{$key}.monthly_management_fee", fn ($v) => (float) $v === 0.0 || $v === null);
    }
}
