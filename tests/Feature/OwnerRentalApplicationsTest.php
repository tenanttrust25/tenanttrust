<?php

namespace Tests\Feature;

use App\Filament\Owner\Resources\RentalApplicationResource\Pages\ListRentalApplications;
use App\Filament\Owner\Resources\RentalApplicationResource\Pages\ViewRentalApplication;
use App\Mail\RentalApplicationMail;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\RentalApplication;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerRentalApplicationsTest extends TestCase
{
    use DatabaseTransactions;

    private function owner(string $email): User
    {
        return User::forceCreate([
            'name' => 'Owner ' . $email,
            'email' => $email,
            'role' => 'owner',
            'password' => bcrypt('secret'),
        ]);
    }

    private function unitFor(User $owner, ?User $coOwner = null): PropertyUnit
    {
        $property = Property::forceCreate([
            'title' => 'Test Property',
            'slug' => 'test-' . uniqid(),
            'address' => '1 Test St',
            'city' => 'Sudbury',
            'province' => 'ON',
            'postal_code' => 'P3A1A1',
            'owner_id' => $owner->id,
            'co_owner_id' => $coOwner?->id,
        ]);

        return PropertyUnit::forceCreate([
            'property_id' => $property->id,
            'unit' => '1',
            'rent_amount' => 1200.00,
        ]);
    }

    private function applicationFor(?PropertyUnit $unit, array $overrides = []): RentalApplication
    {
        return RentalApplication::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'Applicant',
            'phone' => '5551234567',
            'email' => 'applicant@example.com',
            'status' => 'pending',
            'property_unit_id' => $unit?->id,
        ], $overrides));
    }

    public function test_owner_email_resolves_from_the_unit_property(): void
    {
        $owner = $this->owner('resolve.owner@example.com');
        $app = $this->applicationFor($this->unitFor($owner));

        $this->assertSame('resolve.owner@example.com', $app->ownerEmail());
    }

    public function test_application_without_a_linked_owner_returns_null(): void
    {
        $app = $this->applicationFor(null);

        $this->assertNull($app->ownerEmail());
    }

    public function test_owner_only_sees_their_own_applications(): void
    {
        $o1 = $this->owner('owner.one@example.com');
        $o2 = $this->owner('owner.two@example.com');

        $this->applicationFor($this->unitFor($o1), ['first_name' => 'Alice', 'last_name' => 'Anders']);
        $this->applicationFor($this->unitFor($o2), ['first_name' => 'Bruno', 'last_name' => 'Baxter']);

        $this->actingAs($o1);
        Filament::setCurrentPanel(Filament::getPanel('owner'));

        Livewire::test(ListRentalApplications::class)
            ->assertSee('Alice')
            ->assertDontSee('Bruno');
    }

    public function test_owner_can_open_one_of_their_applications(): void
    {
        $owner = $this->owner('view.owner@example.com');
        $app = $this->applicationFor($this->unitFor($owner), ['first_name' => 'Carol', 'last_name' => 'Clark']);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('owner'));

        Livewire::test(ViewRentalApplication::class, ['record' => $app->getRouteKey()])
            ->assertOk()
            ->assertSee('Carol');
    }

    public function test_new_application_is_emailed_to_admin_and_ccs_the_owner(): void
    {
        Mail::fake();
        Storage::fake('public');

        $owner = $this->owner('cc.owner@example.com');
        $unit = $this->unitFor($owner);

        $this->post('/apply', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5551234567',
            'email' => 'jane.applicant@example.com',
            'date_of_birth' => '1990-01-01',
            'property_unit_id' => $unit->id,
            'gov_id' => UploadedFile::fake()->image('id.jpg'),
            'has_pets' => '0',
            'is_smoker' => '0',
            'consent_credit_check' => '1',
            'consent_background_check' => '1',
        ])->assertRedirect(route('rental-application.thank-you'));

        Mail::assertSent(RentalApplicationMail::class, function (RentalApplicationMail $mail) use ($owner) {
            $ccs = collect($mail->cc ?? [])->map(fn ($a) => $a['address'] ?? null);

            return $mail->hasTo('info@tenanttrust.ca') && $ccs->contains($owner->email);
        });
    }

    public function test_new_application_ccs_the_co_owner_too(): void
    {
        Mail::fake();
        Storage::fake('public');

        $owner = $this->owner('primary.owner@example.com');
        $coOwner = $this->owner('co.owner@example.com');
        $unit = $this->unitFor($owner, $coOwner);

        $this->post('/apply', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5551234567',
            'email' => 'jane.applicant2@example.com',
            'date_of_birth' => '1990-01-01',
            'property_unit_id' => $unit->id,
            'gov_id' => UploadedFile::fake()->image('id.jpg'),
            'has_pets' => '0',
            'is_smoker' => '0',
            'consent_credit_check' => '1',
            'consent_background_check' => '1',
        ])->assertRedirect(route('rental-application.thank-you'));

        Mail::assertSent(RentalApplicationMail::class, function (RentalApplicationMail $mail) use ($owner, $coOwner) {
            $ccs = collect($mail->cc ?? [])->map(fn ($a) => $a['address'] ?? null);

            return $ccs->contains($owner->email) && $ccs->contains($coOwner->email);
        });
    }

    public function test_co_owner_sees_the_property_applications(): void
    {
        $owner = $this->owner('own.primary@example.com');
        $coOwner = $this->owner('own.co@example.com');
        $this->applicationFor($this->unitFor($owner, $coOwner), ['first_name' => 'Dana', 'last_name' => 'Dee']);

        $this->actingAs($coOwner);
        Filament::setCurrentPanel(Filament::getPanel('owner'));

        Livewire::test(ListRentalApplications::class)
            ->assertSee('Dana');
    }
}
