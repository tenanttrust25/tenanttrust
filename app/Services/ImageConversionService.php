<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Converts HEIF/HEIC image files (iPhone photos) to true JPEGs.
 *
 * iPhones export photos as HEIC; when such a file is uploaded with a .jpg
 * name, the stored bytes are still HEIF and browsers cannot render them.
 * This service detects HEIF by magic bytes and rewrites the file in place
 * as a real JPEG, keeping the same filename/path so all DB references and
 * URLs stay valid.
 */
class ImageConversionService
{
    private string $converter = '/usr/bin/heif-convert';

    /**
     * Convert a file to JPEG in place if it is actually HEIF/HEIC.
     * Returns true when a conversion happened.
     */
    public function convertIfHeic(string $absolutePath): bool
    {
        if (! is_file($absolutePath) || ! $this->isHeif($absolutePath)) {
            return false;
        }

        $tmp = $absolutePath . '.conv-' . bin2hex(random_bytes(4)) . '.jpg';

        $cmd = escapeshellarg($this->converter)
            . ' -q 92 '
            . escapeshellarg($absolutePath)
            . ' '
            . escapeshellarg($tmp)
            . ' 2>&1';

        exec($cmd, $output, $code);

        if ($code !== 0 || ! is_file($tmp)) {
            @unlink($tmp);
            Log::warning("heif-convert failed for {$absolutePath}", ['output' => $output]);

            return false;
        }

        // Replace the original with the real JPEG (same filename = refs stay valid).
        @chmod($tmp, fileperms($absolutePath) & 0777);
        if (! @rename($tmp, $absolutePath)) {
            @unlink($tmp);
            Log::warning("heif-convert rename failed for {$absolutePath}");

            return false;
        }

        Log::info("Converted HEIF -> JPEG: {$absolutePath}");

        return true;
    }

    /**
     * Detect HEIF/HEIC by magic bytes: ISO BMFF box "ftyp" followed by a
     * brand like heic / heix / mif1 / hevc / avif.
     */
    public function isHeif(string $absolutePath): bool
    {
        $handle = @fopen($absolutePath, 'rb');
        if (! $handle) {
            return false;
        }

        $head = fread($handle, 12);
        fclose($handle);

        if (strlen($head) < 12 || substr($head, 4, 4) !== 'ftyp') {
            return false;
        }

        $brand = substr($head, 8, 4);

        return in_array($brand, ['heic', 'heix', 'hevc', 'mif1', 'msf1', 'avif'], true);
    }

    /**
     * Convert every path in a Filament FileUpload state value
     * (string, or array of strings for multi-file uploads).
     */
    public function convertStatePaths(string|array|null $state, string $disk = 'public'): void
    {
        if ($state === null || $state === '') {
            return;
        }

        $paths = is_array($state) ? $state : [$state];

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $absolute = \Illuminate\Support\Facades\Storage::disk($disk)->path($path);
            $this->convertIfHeic($absolute);
        }
    }
}
