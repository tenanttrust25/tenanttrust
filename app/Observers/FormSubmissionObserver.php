<?php

namespace App\Observers;

use App\Models\FormSubmission;
use App\Models\MailItem;

/**
 * When a filled N-form (N1/N4/N5/N11/...) is created with a generated PDF,
 * queue it in "Outgoing Mail" as a Draft so it can be printed + mailed.
 */
class FormSubmissionObserver
{
    public function created(FormSubmission $submission): void
    {
        $this->sync($submission);
    }

    public function updated(FormSubmission $submission): void
    {
        $this->sync($submission);
    }

    protected function sync(FormSubmission $submission): void
    {
        if (empty($submission->file_path)) {
            return;
        }

        $exists = MailItem::where('source_type', 'form_submission')
            ->where('source_id', $submission->id)
            ->exists();

        if ($exists) {
            return;
        }

        [$line1, $line2, $city, $prov, $postal] = self::parseAddress($submission->unit_address);

        MailItem::create([
            'source_type' => 'form_submission',
            'source_id' => $submission->id,
            'tenant_id' => $submission->tenant_id,
            'property_id' => $submission->property_id,
            'property_unit_id' => $submission->property_unit_id,
            'form_label' => strtoupper((string) ($submission->form_category ?: 'FORM')),
            'recipient_name' => $submission->tenant_names
                ?: optional($submission->tenant)->name
                ?: 'Tenant',
            'address_line1' => $line1 ?: ($submission->unit_address ?: '—'),
            'address_line2' => $line2,
            'city' => $city,
            'province' => $prov,
            'postal_code' => $postal,
            'country' => 'CA',
            'pdf_path' => $submission->file_path,
            'status' => 'draft',
        ]);
    }

    /**
     * Best-effort split of a stored unit address like
     *   "5 Fairview Ave, Sudbury, On, P3B 2M9 — Unit 3"
     * into [street, unit, city, province, postal].
     *
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string, 4: ?string}
     */
    public static function parseAddress(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [null, null, null, null, null];
        }

        $unit = null;
        $parts = preg_split('/\s+[—–-]\s+/u', $raw, 2);
        $base = trim($parts[0]);
        if (! empty($parts[1])) {
            $unit = trim($parts[1]);
        }

        $seg = array_map('trim', explode(',', $base));

        return [
            $seg[0] ?: null,
            $unit,
            $seg[1] ?? null,
            isset($seg[2]) ? strtoupper($seg[2]) : null,
            $seg[3] ?? null,
        ];
    }
}
