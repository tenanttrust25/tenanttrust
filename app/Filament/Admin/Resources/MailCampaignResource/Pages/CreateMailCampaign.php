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
        
        // Build tenant query
        $query = User::where('role', 'tenant');
        
        // If property filter is set, only tenants at those properties
        if (!empty($audienceFilter)) {
            $propertyIds = is_array($audienceFilter) ? $audienceFilter : json_decode($audienceFilter, true);
            $tenantIds = \App\Models\Tenancy::whereIn('property_id', $propertyIds)
                ->where('status', 'active')
                ->pluck('tenant_id')
                ->unique();
            $query->whereIn('id', $tenantIds);
        }
        
        $tenants = $query->get();
        $count = 0;
        
        foreach ($tenants as $tenant) {
            if (empty($tenant->email)) continue;
            
            MailCampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'tenant_id' => $tenant->id,
                'email' => $tenant->email,
                'status' => 'pending',
                'tracking_hash' => Str::random(32),
            ]);
            $count++;
        }
        
        $campaign->update(['recipient_count' => $count]);
    }
}
