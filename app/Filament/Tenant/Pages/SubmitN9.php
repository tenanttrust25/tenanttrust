<?php

namespace App\Filament\Tenant\Pages;

use App\Models\N9Submission;
use App\Models\User;
use App\Services\N9PdfService;
use App\Services\PushNotificationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubmitN9 extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = "heroicon-o-paper-airplane";
    protected static ?string $title = "Submit N9";
    protected static ?string $navigationLabel = "Submit N9";
    protected static ?string $navigationGroup = 'Requests';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = "submit-n9";

    protected static string $view = "filament.tenant.submit-n9";

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $tenancy = $user->activeTenancies()->with(['property.owner', 'propertyUnit'])->first();

        $unit = $tenancy?->propertyUnit?->unit;
        $p = $tenancy?->property;
        $unitAddress = '';
        if ($p) {
            $unitAddress = trim(($unit ? "Unit {$unit}, " : '') . $p->address . ', ' . $p->city . ', ' . $p->province . ' ' . $p->postal_code);
        }

        $this->form->fill([
            'landlord_name' => $p?->owner?->name ?? '',
            'tenant_names' => $user->name,
            'unit_address' => $unitAddress,
            'phone' => $user->phone ?? '',
            'termination_date' => null,
            'signature' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('landlord_name')
                    ->label('Landlord Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tenant_names')
                    ->label('Tenant Name(s)')
                    ->helperText('Include all tenants on the lease.')
                    ->required()
                    ->maxLength(255),
                TextInput::make('unit_address')
                    ->label('Rental Unit Address')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->maxLength(20),
                DatePicker::make('termination_date')
                    ->label('Date the Tenancy Will End')
                    ->helperText('In Ontario, an N9 requires at least 60 days notice.')
                    ->native(false)
                    ->displayFormat('M d, Y')
                    ->required(),
                TextInput::make('signature')
                    ->label('Signature')
                    ->helperText('Type your full legal name. This is your electronic signature.')
                    ->required()
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Resolve property + owner from the tenant's active tenancy so the
        // client portal can show the notice and the owner can be notified.
        $tenancy = Auth::user()->activeTenancies()->with('property')->first();

        $submission = N9Submission::create([
            'tenant_id' => Auth::id(),
            'property_id' => $tenancy?->property_id,
            'owner_id' => $tenancy?->property?->owner_id,
            'landlord_name' => $data['landlord_name'],
            'tenant_names' => $data['tenant_names'],
            'unit_address' => $data['unit_address'],
            'termination_date' => $data['termination_date'],
            'phone' => $data['phone'] ?? null,
            'signature' => $data['signature'],
        ]);

        try {
            $path = app(N9PdfService::class)->generate($submission);
            $submission->update(['file_path' => $path]);

            // File a copy in the tenant's own documents (visible in "Your Files").
            \App\Models\TenantFile::create([
                'tenant_id' => Auth::id(),
                'property_unit_id' => $tenancy?->property_unit_id,
                'uploaded_by' => Auth::id(),
                'name' => 'N9 - Notice to End Tenancy',
                'description' => 'Submitted ' . now()->format('M j, Y') . ' — tenancy ending ' . $submission->termination_date->format('M j, Y'),
                'file_path' => $path,
                'file_type' => 'N9',
            ]);
        } catch (\Exception $e) {
            Log::error('N9 PDF generation failed: ' . $e->getMessage());
        }

        $this->notifyAdmin($submission);

        Notification::make()
            ->success()
            ->title('N9 submitted!')
            ->body('Your move-out notice has been filed. We will be in touch.')
            ->send();

        $this->redirect(static::getUrl());
        $this->form->fill([
            'landlord_name' => $data['landlord_name'],
            'tenant_names' => $data['tenant_names'],
            'unit_address' => $data['unit_address'],
            'phone' => $data['phone'] ?? null,
            'termination_date' => null,
            'signature' => '',
        ]);
    }

    protected function notifyAdmin(N9Submission $submission): void
    {
        // Email the admin inbox with the filled PDF attached.
        try {
            Mail::mailer('smtp')
                ->to('info@tenanttrust.ca')
                ->send(new \App\Mail\N9SubmissionMail($submission));
        } catch (\Exception $e) {
            Log::error('N9 admin email failed: ' . $e->getMessage());
        }

        // Push to all admin users.
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            try {
                app(PushNotificationService::class)->sendToUser(
                    $admin,
                    'New N9 move-out notice',
                    $submission->tenant_names . ' — tenancy ending ' . $submission->termination_date->format('M d, Y'),
                    url('/admin/n9-submissions')
                );
            } catch (\Exception $e) {
                Log::warning("N9 push failed for admin {$admin->id}: " . $e->getMessage());
            }
        }

        // Notify the property owner (client) — email with the filled PDF + push.
        $owner = $submission->owner;
        if (! $owner) {
            return;
        }

        try {
            Mail::mailer('smtp')
                ->to($owner->email)
                ->send(new \App\Mail\N9SubmissionMail($submission));
        } catch (\Exception $e) {
            Log::error('N9 owner email failed: ' . $e->getMessage());
        }

        try {
            app(PushNotificationService::class)->sendToUser(
                $owner,
                'New N9 move-out notice',
                $submission->tenant_names . ' — tenancy ending ' . $submission->termination_date->format('M d, Y'),
                url('/owner/n9-submissions')
            );
        } catch (\Exception $e) {
            Log::warning("N9 push failed for owner {$owner->id}: " . $e->getMessage());
        }
    }
}
