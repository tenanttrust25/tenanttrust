<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLogEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'caller_name',
        'address',
        'phone',
        'called_at',
        'description',
    ];

    protected $casts = [
        'called_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
