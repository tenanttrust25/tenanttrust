<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Filament\Admin\Resources\UserResource\RelationManagers\FormSubmissionsRelationManager;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class N5ReasonModalTest extends TestCase
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
        $u->email = 'ta-' . uniqid() . '@tenanttrust.ca';
        $u->role = 'admin';
        $u->password = bcrypt('secret');
        $u->save();

        return $u;
    }

    private function tenant(): User
    {
        $t = new User();
        $t->name = 'Tenant Reason';
        $t->email = 'tr-' . uniqid() . '@example.com';
        $t->role = 'tenant';
        $t->password = bcrypt('secret');
        $t->save();

        $p = Property::create([
            'title' => 'Reason Prop',
            'slug' => 'reason-prop-' . uniqid(),
            'address' => '9 Reason St',
            'city' => 'Sudbury',
            'province' => 'ON',
            'postal_code' => 'P3A 1A1',
        ]);

        Tenancy::create([
            'tenant_id' => $t->id,
            'property_id' => $p->id,
            'lease_start' => now()->subYear()->toDateString(),
            'rent_amount' => 1000,
            'status' => 'active',
        ]);

        return $t;
    }

    public function test_n5_reason1_checkbox_gets_checked_via_modal_action(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $this->actingAs($admin);

        $form = Form::create([
            'name' => 'N5',
            'file_path' => 'forms/n5-2026-09.pdf',
            'visible_to_tenants' => true,
        ]);

        Livewire::test(FormSubmissionsRelationManager::class, [
            'ownerRecord' => $tenant,
            'pageClass' => EditUser::class,
        ])
            ->callTableAction('generate_form', data: [
                'form_id' => $form->id,
                'n5_reason1' => true,
                'n5_reason1_option' => 'first',
            ])
            ->assertHasNoActionErrors();

        $sub = FormSubmission::where('tenant_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($sub, 'No FormSubmission created');
        $data = $sub->data;

        $reason1 = 'form1[0].sfrmForm[0].Reasons[0].Reason1[0].Reason1[0]';
        $reason1First = 'form1[0].sfrmForm[0].Reasons[0].Reason1[0].Reason1_1[0]#0';

        fwrite(STDERR, "\n[TEST] reason1 key = " . json_encode($data[$reason1] ?? 'MISSING') . "\n");
        fwrite(STDERR, "[TEST] reason1_first = " . json_encode($data[$reason1First] ?? 'MISSING') . "\n");

        $this->assertSame('X', $data[$reason1] ?? null, 'Reason 1 checkbox value');
    }

    public function test_dump_modal_html_for_reason_wiring(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $this->actingAs($admin);
        $form = Form::create(['name' => 'N5', 'file_path' => 'forms/n5-2026-09.pdf', 'visible_to_tenants' => true]);

        $lw = Livewire::test(FormSubmissionsRelationManager::class, [
            'ownerRecord' => $tenant,
            'pageClass' => EditUser::class,
        ]);
        $lw->mountTableAction('generate_form');
        $lw->setTableActionData(['form_id' => $form->id]);
        file_put_contents('/tmp/n5modal.html', $lw->html());
        $this->assertTrue(true);
    }

    public function test_modal_renders_reason_controls_and_checks_box(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();
        $this->actingAs($admin);

        $form = Form::create([
            'name' => 'N5',
            'file_path' => 'forms/n5-2026-09.pdf',
            'visible_to_tenants' => true,
        ]);

        $lw = Livewire::test(FormSubmissionsRelationManager::class, [
            'ownerRecord' => $tenant,
            'pageClass' => EditUser::class,
        ]);

        $lw->mountTableAction('generate_form');
        $lw->setTableActionData(['form_id' => $form->id]);

        // The reason toggle must render in the modal.
        $lw->assertSee('Include Reason 1');

        // Flip the toggle — the wording options should now appear.
        $lw->setTableActionData(['n5_reason1' => true]);
        $lw->assertSee('You have 7 days to stop the activities');
        $lw->assertSee('I can apply to the LTB immediately');

        $lw->setTableActionData(['n5_reason1_option' => 'first']);

        $lw->callMountedTableAction();
        $lw->assertHasNoActionErrors();

        $sub = FormSubmission::where('tenant_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($sub, 'No FormSubmission created');
        $reason1 = 'form1[0].sfrmForm[0].Reasons[0].Reason1[0].Reason1[0]';
        fwrite(STDERR, "\n[INTERACTION] reason1 = " . json_encode($sub->data[$reason1] ?? 'MISSING') . "\n");
        $this->assertSame('X', $sub->data[$reason1] ?? null, 'Reason 1 checkbox via interaction');
    }
}
