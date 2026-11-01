<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Renders a Tenant Trust letterhead letter to a PDF and stores it under
 * storage/app/public/mail-items/ so it can be picked up by the Outgoing Mail
 * section and mailed via PostGrid.
 */
class LetterPdfService
{
    /**
     * @param  array{recipient_name:string, address_line1:string, address_line2?:?string,
     *               city?:?string, province?:?string, postal_code?:?string,
     *               subject?:?string, salutation?:?string, body:string, signer_name?:?string}  $data
     * @return string relative path on the "public" disk (mail-items/....pdf)
     */
    public function generate(array $data): string
    {
        $paragraphs = collect(preg_split('/\r\n|\r|\n/', (string) $data['body']))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $logoPath = public_path('images/tenant-trust-logo.jpg');
        $logo = is_file($logoPath)
            ? 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents($logoPath))
            : null;

        $view = view('pdfs.letter', [
            'recipientName' => $data['recipient_name'],
            'addressLine1' => $data['address_line1'],
            'addressLine2' => $data['address_line2'] ?? null,
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? null,
            'postalCode' => $data['postal_code'] ?? null,
            'date' => now()->format('F j, Y'),
            'subject' => $data['subject'] ?? null,
            'salutation' => $data['salutation'] ?? null,
            'paragraphs' => $paragraphs,
            'signerName' => $data['signer_name'] ?? 'Steve Ouimette',
            'logo' => $logo,
        ])->render();

        $bytes = Pdf::loadHTML($view)->setPaper('letter', 'portrait')->output();

        $filename = 'Letter_' . now()->format('Ymd_His') . '_' . Str::upper(Str::random(4)) . '.pdf';
        $path = 'mail-items/' . $filename;

        Storage::disk('public')->put($path, $bytes);

        return $path;
    }
}
