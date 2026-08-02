<?php

namespace App\Filament\Owner\Pages;

use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\TenantFile;
use App\Models\Tenancy;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Portfolio extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-building-office";
    protected static ?string $title = "Portfolio";
    protected static ?string $navigationLabel = "Portfolio";
    protected static ?int $navigationSort = 4;
    protected static string $view = "filament.owner.portfolio";

    public function getProperties()
    {
        return Property::where("owner_id", Auth::id())->get();
    }

    public function getTenantsForProperty($propertyId)
    {
        return DB::table("invoices")
            ->join("users", "users.id", "=", "invoices.tenant_id")
            ->where("invoices.property_id", $propertyId)
            ->select("users.id", "users.name", "users.email", "users.phone")
            ->distinct()
            ->get();
    }

    public function getFilesForTenant($tenantId)
    {
        return TenantFile::where("tenant_id", $tenantId)->get();
    }

    public function getRentAmount($propertyId)
    {
        return Tenancy::where("property_id", $propertyId)
            ->where("status", "active")
            ->sum("rent_amount") ?: 0;
    }
}
