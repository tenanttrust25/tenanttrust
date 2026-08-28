<?php

namespace App\Filament\Components;

use App\Services\ImageConversionService;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Log;

/**
 * FileUpload that automatically converts HEIC/HEIF uploads (iPhone photos)
 * to real JPEGs in place, right after the file is stored. Same filename is
 * kept, so DB references and URLs stay valid — the bytes just become JPEG.
 */
class ImageUpload extends FileUpload
{
    public function saveUploadedFiles(): void
    {
        parent::saveUploadedFiles();

        try {
            app(ImageConversionService::class)->convertStatePaths(
                $this->getState(),
                $this->getDiskName(),
            );
        } catch (\Throwable $e) {
            Log::warning('HEIC auto-convert failed: ' . $e->getMessage());
        }
    }
}
