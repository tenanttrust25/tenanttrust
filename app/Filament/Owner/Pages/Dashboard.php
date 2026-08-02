<?php

namespace App\Filament\Owner\Pages;

use App\Models\Payout;
use App\Models\Property;
use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\TenantFile;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-home";
    protected static ?string $title = "Dashboard";
    protected static ?string $navigationLabel = "Dashboard";
    protected static ?int $navigationSort = 1;

    protected static string $view = "filament.owner.dashboard";

    public $totalUnits = 0;
    public $totalUnitCount = 0;
    public $occupiedUnits = 0;
    public $vacantUnits = 0;
    public $occupancyRate = 0;
    public $expectedIncome = 0;
    public $collectedIncome = 0;
    public $openExpenses = 0;
    public $netIncome = 0;
    public $pendingPayouts = 0;
    public $totalPayouts = 0;
    public $totalFeesPaid = 0;
    public $openTickets = 0;
    public $criticalTickets = 0;
    public $totalDocuments = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $propertyIds = $user->ownedProperties()->pluck('id');

        $this->totalUnits = $propertyIds->count();
        $this->totalUnitCount = Property::whereIn('id', $propertyIds)->sum('unit_count');
        $this->occupiedUnits = Property::whereIn('id', $propertyIds)->where('status', 'occupied')->count();
        $this->vacantUnits = Property::whereIn('id', $propertyIds)->where('status', 'vacant')->count();
        $this->occupancyRate = $this->totalUnits > 0
            ? round(($this->occupiedUnits / $this->totalUnits) * 100)
            : 0;

        $this->expectedIncome = Invoice::whereIn('property_id', $propertyIds)->sum('amount');
        $this->collectedIncome = Invoice::whereIn('property_id', $propertyIds)->where('status', 'paid')->sum('amount');

        // Payout stats
        $this->pendingPayouts = Payout::where('owner_id', $user->id)->where('status', 'pending')->sum('net_amount');
        $this->totalPayouts = Payout::where('owner_id', $user->id)->where('status', 'paid')->sum('net_amount');
        $this->totalFeesPaid = Payout::where('owner_id', $user->id)->sum('management_fee');

        $this->openTickets = MaintenanceTicket::whereIn('property_id', $propertyIds)
            ->whereIn('status', ['open', 'in_progress'])->count();
        $this->criticalTickets = MaintenanceTicket::whereIn('property_id', $propertyIds)
            ->where('priority', 'emergency')->count();
        $this->totalDocuments = TenantFile::count();

        $this->openExpenses = $this->expectedIncome - $this->collectedIncome;
        $this->netIncome = $this->collectedIncome - $this->totalFeesPaid;
    }

    public function getTotalUnits(): int { return $this->totalUnits; }
    public function getOccupancyRate(): int { return $this->occupancyRate; }
    public function getCollectedIncome(): float { return $this->collectedIncome; }
    public function getExpectedIncome(): float { return $this->expectedIncome; }
    public function getNetIncome(): float { return $this->netIncome; }
    public function getOpenExpenses(): float { return $this->openExpenses; }
    public function getOpenTickets(): int { return $this->openTickets; }
    public function getCriticalTickets(): int { return $this->criticalTickets; }
    public function getTotalDocuments(): int { return $this->totalDocuments; }
    public function getTotalUnitCount(): int { return $this->totalUnitCount; }
    public function getPendingPayouts(): float { return $this->pendingPayouts; }
    public function getTotalPayouts(): float { return $this->totalPayouts; }
    public function getTotalFeesPaid(): float { return $this->totalFeesPaid; }
}
