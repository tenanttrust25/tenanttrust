<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'file_path',
        'visible_to_tenants',
    ];

    protected $casts = [
        'visible_to_tenants' => 'boolean',
    ];

    /**
     * Remove the uploaded file from storage when the form record is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Form $form): void {
            if ($form->file_path) {
                Storage::disk('public')->delete($form->file_path);
            }
        });
    }

    public function url(): string
    {
        return route('files.form', $this);
    }
}
