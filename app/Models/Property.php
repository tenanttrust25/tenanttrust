<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'title', 'description', 'price', 'address', 'city', 'province',
        'postal_code', 'status', 'type', 'bedrooms', 'bathrooms',
        'square_feet', 'amenities', 'images', 'owner_id'
    ];

    protected $casts = [
        'amenities' => 'array',
        'images' => 'array',
        'price' => 'decimal:2',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}
