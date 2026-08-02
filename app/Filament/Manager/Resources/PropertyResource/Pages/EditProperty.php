<?php

namespace App\Filament\Manager\Resources\PropertyResource\Pages;

use App\Filament\Manager\Resources\PropertyResource;
use App\Models\TenantFile;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return "Property updated!";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate that no tenant is assigned to multiple active tenancies
        $tenancies = $data['tenancies'] ?? [];
        $propertyId = $this->record->id;

        foreach ($tenancies as $tenancy) {
            if (($tenancy['status'] ?? '') === 'active' && isset($tenancy['tenant_id'])) {
                $existing = \App\Models\Tenancy::where('status', 'active')
                    ->where('tenant_id', $tenancy['tenant_id'])
                    ->where('property_id', '!=', $propertyId);

                // If editing an existing tenancy, exclude the current record
                if (isset($tenancy['id'])) {
                    $existing->where('id', '!=', $tenancy['id']);
                }

                if ($existing->exists()) {
                    $tenantName = \App\Models\User::find($tenancy['tenant_id'])?->name ?? 'Unknown';
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], [
                            'tenancies' => "{$tenantName} already has an active tenancy at another property."
                        ])
                    );
                }
            }
        }

        return $data;
    }


    protected function afterSave(): void
    {
        // Handle tenant document uploads from the standalone repeater
        $data = $this->form->getRawState();
        $uploads = $data["tenantFilesUpload"] ?? [];

        foreach ($uploads as $upload) {
            if (!isset($upload["file_path"]) || !isset($upload["tenant_id"])) {
                continue;
            }

            // FileUpload stores the path as a string
            $filePath = $upload["file_path"];
            if (is_array($filePath)) {
                $filePath = $filePath[0] ?? null;
            }
            if (!$filePath) {
                continue;
            }

            // Check if this file was already created (avoid duplicates on re-save)
            $exists = TenantFile::where("file_path", $filePath)
                ->where("tenant_id", $upload["tenant_id"])
                ->exists();

            if (!$exists) {
                TenantFile::create([
                    "tenant_id" => $upload["tenant_id"],
                    "uploaded_by" => auth()->id(),
                    "name" => $upload["name"] ?? "Uploaded Document",
                    "file_path" => $filePath,
                    "file_type" => $upload["file_type"] ?? "lease",
                    "description" => $upload["description"] ?? null,
                ]);
            }
        }
    }
}
