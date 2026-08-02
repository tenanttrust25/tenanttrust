<?php

namespace App\Filament\Admin\Resources\MailCampaignResource\Widgets;

use App\Models\MailCampaign;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $total = MailCampaign::count();
        $sent = MailCampaign::where('status', 'sent')->count();
        $draft = MailCampaign::where('status', 'draft')->count();
        $scheduled = MailCampaign::where('status', 'scheduled')->count();

        $totalSent = MailCampaign::sum('sent_count');
        $totalOpens = MailCampaign::sum('open_count');
        $openRate = $totalSent > 0 ? round(($totalOpens / $totalSent) * 100) : 0;

        return [
            Stat::make('Total Campaigns', $total)
                ->description($sent . ' sent, ' . $draft . ' draft, ' . $scheduled . ' scheduled')
                ->color('primary'),
            Stat::make('Emails Sent', number_format($totalSent))
                ->color('success'),
            Stat::make('Open Rate', $openRate . '%')
                ->description(number_format($totalOpens) . ' total opens')
                ->color('warning'),
        ];
    }
}
