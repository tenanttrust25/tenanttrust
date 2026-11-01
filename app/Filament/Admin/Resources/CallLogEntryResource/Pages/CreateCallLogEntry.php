<?php

namespace App\Filament\Admin\Resources\CallLogEntryResource\Pages;

use App\Filament\Admin\Resources\CallLogEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCallLogEntry extends CreateRecord
{
    protected static string $resource = CallLogEntryResource::class;
}
