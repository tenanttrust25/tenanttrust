<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RentalApplicationResource\Pages\ViewRentalApplication;
use App\Mail\RentalApplicationApprovedMail;
use App\Mail\RentalApplicationDeclinedMail;
use App\Models\RentalApplication;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class DeclineRentalApplicationTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = new User();
        $admin->name = 'Admin';
        $admin->email = 'admin@tenanttrust.ca';
        $admin->role = 'admin';
        $admin->password = bcrypt('secret');
        $admin->save();

        return $admin;
    }

    private function application(array $overrides = []): RentalApplication
    {
        return RentalApplication::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'Applicant',
            'phone' => '5551234567',
            'email' => 'applicant@example.com',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_decline_records_reasons_and_notes(): void
    {
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $app = $this->application();

        Livewire::test(ViewRentalApplication::class, ['record' => $app->getRouteKey()])
            ->callAction('markDeclined', data: [
                'reasons' => ['No photo ID', 'Missing address'],
                'notes' => 'Please reapply with ID.',
            ])
            ->assertHasNoActionErrors();

        $app->refresh();
        $this->assertSame('declined', $app->status);
        $this->assertSame(['No photo ID', 'Missing address'], $app->decline_reasons);
        $this->assertSame('Please reapply with ID.', $app->decline_notes);
    }

    public function test_decline_requires_a_reason(): void
    {
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $app = $this->application();

        Livewire::test(ViewRentalApplication::class, ['record' => $app->getRouteKey()])
            ->callAction('markDeclined', data: ['reasons' => [], 'notes' => ''])
            ->assertHasActionErrors(['reasons']);

        $this->assertSame('pending', $app->fresh()->status);
    }

    public function test_decline_emails_applicant_and_hides_sensitive_reasons(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $app = $this->application();

        Livewire::test(ViewRentalApplication::class, ['record' => $app->getRouteKey()])
            ->callAction('markDeclined', data: [
                'reasons' => ['No photo ID', 'Credit concerns'],
                'notes' => 'internal note — must not leak',
            ])
            ->assertHasNoActionErrors();

        Mail::assertSent(RentalApplicationDeclinedMail::class, function (RentalApplicationDeclinedMail $mail) use ($app) {
            $html = $mail->render();

            return $mail->hasTo($app->email)
                && $mail->visibleReasons() === ['No photo ID']
                && str_contains($html, 'No photo ID')
                && !str_contains($html, 'Credit concerns')
                && !str_contains($html, 'internal note');
        });
    }

    public function test_decline_with_only_sensitive_reasons_sends_generic_email(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $app = $this->application();

        Livewire::test(ViewRentalApplication::class, ['record' => $app->getRouteKey()])
            ->callAction('markDeclined', data: [
                'reasons' => ['Eviction history', 'Credit concerns'],
                'notes' => '',
            ])
            ->assertHasNoActionErrors();

        Mail::assertSent(RentalApplicationDeclinedMail::class, function (RentalApplicationDeclinedMail $mail) {
            return $mail->visibleReasons() === [];
        });
    }

    public function test_approve_emails_applicant_with_deposit_and_next_steps(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $property = \App\Models\Property::forceCreate([
            'title' => 'Test Property',
            'slug' => 'test-' . uniqid(),
            'address' => '1 Test St',
            'city' => 'Sudbury',
            'province' => 'ON',
            'postal_code' => 'P3A1A1',
        ]);
        $unit = \App\Models\PropertyUnit::forceCreate([
            'property_id' => $property->id,
            'unit' => '1',
            'rent_amount' => 1375.00,
        ]);

        $app = $this->application(['property_unit_id' => $unit->id]);

        Livewire::test(ViewRentalApplication::class, ['record' => $app->getRouteKey()])
            ->callAction('markApproved');

        $this->assertSame('approved', $app->fresh()->status);

        Mail::assertSent(RentalApplicationApprovedMail::class, function (RentalApplicationApprovedMail $mail) use ($app) {
            $html = $mail->render();

            return $mail->hasTo($app->email)
                && $mail->monthlyRent() === 1375.0
                && str_contains($html, 'msugrim1@gmail.com')
                && str_contains($html, '2,750.00')
                && str_contains($html, 'Square One')
                && str_contains($html, 'Pay Now');
        });
    }
}
