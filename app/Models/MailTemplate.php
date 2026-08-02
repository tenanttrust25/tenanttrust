<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailTemplate extends Model
{
    protected $fillable = ['name', 'subject', 'body', 'category'];

    protected $table = 'mail_templates';

    public function campaigns(): HasMany
    {
        return $this->hasMany(MailCampaign::class, 'template_id');
    }
}
