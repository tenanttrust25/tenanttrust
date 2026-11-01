<?php

namespace App\Filament\Admin\Pages;

use App\Support\TenantTrustSettings;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'Settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.admin.settings';

    public bool $hst_enabled = false;

    public float $hst_rate = 13.0;

    public string $postgrid_mode = 'test';

    public string $postgrid_test_key = '';

    public string $postgrid_live_key = '';

    public function mount(): void
    {
        $this->hst_enabled = TenantTrustSettings::hstEnabled();
        $this->hst_rate = TenantTrustSettings::hstRate();
        $this->postgrid_mode = TenantTrustSettings::postgridMode();
        $this->postgrid_test_key = TenantTrustSettings::postgridTestKey();
        $this->postgrid_live_key = TenantTrustSettings::postgridLiveKey();
    }

    public function save(): void
    {
        $this->validate([
            'hst_rate' => 'required|numeric|min:0|max:30',
            'postgrid_mode' => 'required|in:test,live',
        ]);

        TenantTrustSettings::put([
            'hst_enabled' => (bool) $this->hst_enabled,
            'hst_rate' => (float) $this->hst_rate,
            'postgrid_mode' => $this->postgrid_mode,
            'postgrid_test_key' => trim($this->postgrid_test_key),
            'postgrid_live_key' => trim($this->postgrid_live_key),
        ]);

        Notification::make()
            ->title('Settings saved')
            ->body($this->hst_enabled
                ? "HST at {$this->hst_rate}% will be added to management fees and withheld from owner payouts."
                : 'HST is off — payouts deduct the management fee only.')
            ->success()
            ->send();
    }
}
