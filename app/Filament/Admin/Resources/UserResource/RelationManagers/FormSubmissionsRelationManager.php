<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use App\Mail\FormSubmissionMail;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\TenantFile;
use App\Models\User;
use App\Services\FormFillService;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FormSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'formSubmissions';

    protected static ?string $title = 'Forms';

    protected static ?string $recordTitleAttribute = 'form_category';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('form.name')
                    ->label('Form')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('form_category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'n1' => 'warning',
                        'n4' => 'danger',
                        'n5' => 'danger',
                        'n11' => 'success',
                        'n9' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                Tables\Columns\TextColumn::make('unit_address')
                    ->label('Rental Unit')
                    ->limit(35)
                    ->searchable(),
                Tables\Columns\TextColumn::make('landlord_name')
                    ->label('Landlord')
                    ->limit(20),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Generated')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('generate_form')
                    ->label('Generate Form')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('form_id')
                            ->label('Form Template')
                            ->options(fn (): array => app(FormFillService::class)->options())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state): void {
                                // Filament only hydrates ->default() on fields mounted at
                                // form creation; dynamically-added fields need explicit state.
                                $service = app(FormFillService::class);
                                $form = Form::find((int) $state);
                                if (! $form) {
                                    return;
                                }
                                $category = $service->categoryFor($form);
                                if (! $category) {
                                    return;
                                }
                                $prefill = $service->prefillData(
                                    $category,
                                    $service->prefill($this->getOwnerRecord()),
                                );
                                foreach ($prefill as $key => $value) {
                                    $set($key, $value);
                                }
                            })
                            ->helperText('Select the LTB form you want to file for this tenant.'),
                        Forms\Components\Section::make('Form Details')
                            ->description('Prefilled from the tenant record — adjust as needed.')
                            ->schema(fn (Get $get): array => $this->schemaFor($get('form_id')))
                            ->columns(2)
                            ->visible(fn (Get $get): bool => filled($get('form_id'))),
                    ])
                    ->action(fn (array $data) => $this->generateForm($data)),
            ])
            ->actions([
                Tables\Actions\Action::make('email_to_tenant')
                    ->label('Email to Tenant')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Email form to tenant')
                    ->modalDescription(fn (FormSubmission $record): string => 'Send the ' . strtoupper($record->form_category ?? 'form') . ' PDF to ' . ($record->tenant?->email ?? 'the tenant') . '?')
                    ->visible(fn (FormSubmission $record): bool => (bool) $record->file_path && filled($record->tenant?->email))
                    ->action(fn (FormSubmission $record) => $this->emailToTenant($record)),
                Tables\Actions\Action::make('open_pdf')
                    ->label('Open PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (FormSubmission $record): ?string => $record->file_path
                        ? Storage::disk('public')->url($record->file_path)
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (FormSubmission $record): bool => (bool) $record->file_path),
                Tables\Actions\DeleteAction::make()
                    ->action(function (FormSubmission $record) {
                        // Clean up the physical PDF + linked tenant file row.
                        if ($record->file_path) {
                            Storage::disk('public')->delete($record->file_path);
                            TenantFile::where('file_path', $record->file_path)->delete();
                        }
                        $record->delete();
                    }),
            ])
            ->bulkActions([]);
    }

    protected function schemaFor(mixed $formId): array
    {
        if (! $formId) {
            return [];
        }

        $form = Form::find((int) $formId);
        if (! $form) {
            return [];
        }

        $service = app(FormFillService::class);
        $category = $service->categoryFor($form);
        if (! $category) {
            return [
                Forms\Components\Placeholder::make('no_map')
                    ->label('No fill map')
                    ->content('This form has no prefill mapping yet — add one to FormFillService.'),
            ];
        }

        return $service->schema($category, $service->prefill($this->getOwnerRecord()));
    }

    protected function generateForm(array $data): void
    {
        /** @var User $tenant */
        $tenant = $this->getOwnerRecord();
        $service = app(FormFillService::class);

        $form = Form::find((int) ($data['form_id'] ?? 0));
        if (! $form) {
            Notification::make()->danger()->title('Form not found')->send();

            return;
        }

        $category = $service->categoryFor($form);
        if (! $category) {
            Notification::make()->danger()->title('No fill map for this form')->send();

            return;
        }

        $ctx = $service->prefill($tenant);
        $values = $service->buildValues($category, $data, $ctx);
        $styles = $service->styles($category);

        try {
            $path = $service->generate($form, $category, $values, $styles);
        } catch (Throwable $e) {
            // Full traceback goes to the log; the notification shows the real cause.
            logger()->error('Form generation failed', [
                'form' => $form->name,
                'category' => $category,
                'tenant' => $tenant->name,
                'error' => $e->getMessage(),
            ]);

            $message = $e->getMessage();
            $lines = preg_split('/\R/', trim($message));
            $summary = collect($lines)->filter(fn (string $l): bool => str_contains($l, 'Error') || preg_match('/\w+Error|Exception|Invalid|Cannot/', $l))->last() ?? last($lines);

            Notification::make()
                ->danger()
                ->title('Form generation failed')
                ->body('Details: ' . $summary)
                ->send();

            return;
        }

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'tenant_id' => $tenant->id,
            'property_unit_id' => $ctx['property_unit_id'],
            'property_id' => $ctx['property_id'],
            'owner_id' => $ctx['owner_id'],
            'form_category' => $category,
            'landlord_name' => $ctx['landlord_name'],
            'tenant_names' => $ctx['tenant_names'],
            'unit_address' => $ctx['unit_address'],
            'data' => $values,
            'file_path' => $path,
        ]);

        // Auto-attach to the tenant's file (per Steve).
        TenantFile::create([
            'tenant_id' => $tenant->id,
            'property_unit_id' => $ctx['property_unit_id'],
            'uploaded_by' => auth()->id(),
            'name' => $form->name . ' — ' . now()->format('M j, Y'),
            'description' => 'Auto-generated from the Forms tab on the user record',
            'file_path' => $path,
            'file_type' => strtoupper($category),
        ]);

        Notification::make()
            ->success()
            ->title('Form generated')
            ->body($form->name . ' filled and attached to ' . $tenant->name . "'s file.")
            ->actions([
                \Filament\Notifications\Actions\Action::make('open')
                    ->label('Open PDF')
                    ->url(Storage::disk('public')->url($path))
                    ->openUrlInNewTab(),
            ])
            ->send();
    }

    protected function emailToTenant(FormSubmission $record): void
    {
        $tenant = $record->tenant ?? $this->getOwnerRecord();

        if (! $tenant?->email) {
            Notification::make()->danger()->title('No email on tenant record')->send();

            return;
        }

        try {
            Mail::to($tenant->email)->send(new FormSubmissionMail($record));

            Notification::make()
                ->success()
                ->title('Form emailed')
                ->body('Sent to ' . $tenant->email)
                ->send();
        } catch (Throwable $e) {
            logger()->error('Form email failed', [
                'submission' => $record->id,
                'tenant' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Email failed')
                ->body($e->getMessage())
                ->send();
        }
    }
}
