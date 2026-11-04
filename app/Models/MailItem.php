<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailItem extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'tenant_id',
        'property_id',
        'property_unit_id',
        'form_label',
        'recipient_name',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'postal_code',
        'country',
        'single_sheet',
        'pdf_path',
        'status',
        'postgrid_id',
        'postgrid_status',
        'postgrid_url',
        'mode',
        'cost',
        'sent_at',
        'error',
        'created_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'cost' => 'decimal:2',
        'single_sheet' => 'boolean',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'property_unit_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function isSendable(): bool
    {
        return in_array($this->status, ['draft', 'failed'], true) && ! empty($this->pdf_path);
    }

    public function pdfUrl(): ?string
    {
        return $this->pdf_path ? route('files.mail-item', $this) : null;
    }

    public function fullAddress(): string
    {
        return trim(implode(', ', array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->province,
            $this->postal_code,
        ])));
    }
}
