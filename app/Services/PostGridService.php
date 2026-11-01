<?php

namespace App\Services;

use App\Models\MailItem;
use App\Support\TenantTrustSettings;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends letters through the PostGrid Print & Mail API.
 *
 * PDFs are uploaded as multipart/form-data to POST /print-mail/v1/letters.
 * PostGrid stamps the destination + return addresses, prints, folds, stamps
 * and hands the letter to Canada Post.
 *
 * NOTE: PostGrid's Cloudflare blocks the default PHP/Guzzle user-agent, so a
 * browser-like User-Agent header must be sent.
 */
class PostGridService
{
    public const ENDPOINT = 'https://api.postgrid.com/print-mail/v1/letters';

    public const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    public function mode(): string
    {
        return TenantTrustSettings::postgridMode();
    }

    public function keyFor(?string $mode = null): string
    {
        $mode = $mode ?: $this->mode();

        $settingsKey = $mode === 'live'
            ? TenantTrustSettings::postgridLiveKey()
            : TenantTrustSettings::postgridTestKey();

        if ($settingsKey !== '') {
            return $settingsKey;
        }

        $value = $mode === 'live'
            ? (config('tenanttrust.postgrid.live_key') ?: env('POSTGRID_LIVE_KEY'))
            : (config('tenanttrust.postgrid.test_key') ?: env('POSTGRID_TEST_KEY'));

        return trim((string) $value);
    }

    /**
     * Send a single mail item. Updates and returns the model.
     *
     * @throws RuntimeException on a missing key/PDF or a PostGrid error.
     */
    public function send(MailItem $item, ?string $mode = null): MailItem
    {
        $mode = $mode ?: $this->mode();
        $key = $this->keyFor($mode);

        if ($key === '') {
            throw new RuntimeException("PostGrid {$mode} API key is not configured (Admin > Settings).");
        }

        if (! $item->pdf_path || ! is_file(storage_path('app/public/' . $item->pdf_path))) {
            throw new RuntimeException('Letter PDF is missing for this item.');
        }

        [$first, $last] = $this->splitName($item->recipient_name);

        $fields = array_filter([
            // Return address (Tenant Trust)
            'from[companyName]'   => 'Tenant Trust',
            'from[addressLine1]'  => '3-474 McKim St',
            'from[city]'          => 'Sudbury',
            'from[provinceOrState]' => 'ON',
            'from[postalOrZip]'   => 'P3C 2L6',
            'from[countryCode]'   => 'CA',
            // Destination
            'to[firstName]'       => $first,
            'to[lastName]'        => $last,
            'to[addressLine1]'    => $item->address_line1,
            'to[addressLine2]'    => $item->address_line2,
            'to[city]'            => $item->city,
            'to[provinceOrState]' => $item->province,
            'to[postalOrZip]'     => $item->postal_code,
            'to[countryCode]'     => $item->country ?: 'CA',
            // Options
            // 1-sheet (addresses stamped on page 1) requires the letter to leave
            // the top address zone blank (1.375in from left, 0.75in from top,
            // 2.5in x 2.5in); otherwise PostGrid cancels it. Safe default is the
            // dedicated address page (2 sheets).
            'addressPlacement'    => $item->single_sheet ? 'top_first_page' : 'insert_blank_page',
            'size'                => 'us_letter',
            'description'         => 'Tenant Trust ' . ($item->form_label ?: 'letter') . ' - ' . $item->recipient_name,
        ], fn ($v) => $v !== null && $v !== '');

        $response = Http::withHeaders([
            'x-api-key' => $key,
            'User-Agent' => self::UA,
            'Accept' => 'application/json',
        ])
            ->attach('pdf', file_get_contents(storage_path('app/public/' . $item->pdf_path)), basename($item->pdf_path))
            ->post(self::ENDPOINT, $fields);

        if (! $response->successful()) {
            $item->update([
                'status' => 'failed',
                'mode' => $mode,
                'error' => 'HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 500),
            ]);

            throw new RuntimeException('PostGrid error: ' . mb_substr($response->body(), 0, 300));
        }

        $data = $response->json();

        $item->update([
            // In test mode keep the item sendable (draft) so it can still be
            // mailed for real later; only a live send marks it as sent.
            'status' => $mode === 'live' ? 'sent' : 'draft',
            'mode' => $mode,
            'postgrid_id' => $data['id'] ?? null,
            'postgrid_status' => $data['status'] ?? null,
            'postgrid_url' => $data['url'] ?? null,
            'sent_at' => now(),
            'error' => null,
        ]);

        return $item;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function splitName(?string $name): array
    {
        $name = trim((string) $name);
        if ($name === '') {
            return [null, null];
        }

        $parts = preg_split('/\s+/', $name);
        $first = array_shift($parts);

        return [$first, $parts ? implode(' ', $parts) : null];
    }
}
