<?php

namespace App\Filament\Admin\Resources\MailCampaignResource\Pages;

use App\Filament\Admin\Resources\MailCampaignResource;
use App\Models\MailCampaign;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class ViewCampaignRecipients extends Page implements HasTable
{
    use InteractsWithTable;

    public MailCampaign $record;
    protected static string $resource = MailCampaignResource::class;
    protected static string $view = 'filament.admin.pages.campaign-recipients';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->record->recipients()->getQuery()->with('tenant'))
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'sent',
                        'success' => 'opened',
                        'warning' => 'clicked',
                        'danger' => 'bounced',
                    ]),
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('opened_at')
                    ->label('Opened')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('click_count')
                    ->label('Clicks')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'opened' => 'Opened',
                        'clicked' => 'Clicked',
                        'bounced' => 'Bounced',
                        'unsubscribed' => 'Unsubscribed',
                    ]),
            ])
            ->defaultSort('status');
    }

    public function getBreadcrumbs(): array
    {
        return [
            MailCampaignResource::getUrl('index') => 'Campaigns',
            MailCampaignResource::getUrl('edit', ['record' => $this->record]) => $this->record->title,
            'Recipients',
        ];
    }
}
