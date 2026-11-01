<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class N9Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'owner_id',
        'landlord_name',
        'tenant_names',
        'unit_address',
        'termination_date',
        'phone',
        'signature',
        'file_path',
    ];

    protected $casts = [
        'termination_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
