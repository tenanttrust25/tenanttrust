<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(["name", "email", "password", "role", "phone", "address", "start_date", "end_date", "stripe_account_id", "stripe_onboarding_complete", "stripe_account_created_at"])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'tenant_id');
    }

    public function tenantFiles(): HasMany
    {
        return $this->hasMany(TenantFile::class, 'tenant_id');
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(TenantFile::class, 'uploaded_by');
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class, 'tenant_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'tenant_id');
    }

    public function tenancies(): HasMany
    {
        return $this->hasMany(Tenancy::class, 'tenant_id');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class, 'user_id');
    }

    public function activeTenancies(): HasMany
    {
        return $this->tenancies()->where('status', 'active');
    }

    /** Get all tenant IDs sharing the same unit(s) as this user */
    public function getUnitTenantIds(): array
    {
        $unitIds = $this->activeTenancies()->pluck('property_unit_id')->filter()->toArray();
        if (empty($unitIds)) {
            return [$this->id];
        }
        return Tenancy::whereIn('property_unit_id', $unitIds)
            ->where('status', 'active')
            ->pluck('tenant_id')
            ->unique()
            ->toArray();
    }

    /** Get the first active property unit ID for this tenant */
    public function getUnitId(): ?int
    {
        return $this->activeTenancies()->value('property_unit_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'owner_id');
    }

    public function ownedProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }
}
