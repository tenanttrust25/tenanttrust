<?php

namespace App\Filament\Admin\Resources\MailCampaignResource\Pages;

use App\Filament\Admin\Resources\MailCampaignResource;
use App\Models\MailCampaignRecipient;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateMailCampaign extends CreateRecord
{
    protected static string $resource = MailCampaignResource::class;

    protected function afterCreate(): void
    {
        $campaign = $this->record;
        $audienceFilter = $campaign->audience_filter;
        $audienceType = $campaign->audience_type ?? 'tenants';
        $propertyIds = !empty($audienceFilter)
            ? (is_array($audienceFilter) ? $audienceFilter : json_decode($audienceFilter, true))
            : [];

        $recipients = collect();

        // Tenants (optionally filtered by property)
        if (in_array($audienceType, ['tenants', 'both'])) {
            $query = User::where('role', 'tenant');
            if (!empty($propertyIds)) {
                $tenantIds = \App\Models\Tenancy::whereIn('property_id', $propertyIds)
                    ->where('status', 'active')
                    ->pluck('tenant_id')
                    ->unique();
                $query->whereIn('id', $tenantIds);
            }
            $recipients = $recipients->merge($query->get());
        }

        // Clients (owners) — optionally filtered to owners of selected properties
        if (in_array($audienceType, ['clients', 'both'])) {
            $query = User::where('role', 'owner');
            if (!empty($propertyIds)) {
                $ownerIds = \App\Models\Property::whereIn('id', $propertyIds)
                    ->pluck('owner_id')
                    ->unique();
                $query->whereIn('id', $ownerIds);
            }
            $recipients = $recipients->merge($query->get());
        }

        // De-duplicate by user id, drop users without email
        $recipients = $recipients->unique('id')->filter(fn ($u) => !empty($u->email));
        $count = 0;

        foreach ($recipients as $user) {
            MailCampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'tenant_id' => $user->id,
                'email' => $user->email,
                'status' => 'pending',
                'tracking_hash' => Str::random(32),
            ]);
            $count++;
        }

        $campaign->update(['recipient_count' => $count]);
    }
}
