<?php

namespace App\Observers;

use App\Models\MailItem;
use App\Models\N9Submission;

/**
 * When a filled N9 (tenant's notice to end tenancy) is created with a PDF,
 * queue it in "Outgoing Mail" as a Draft.
 */
class N9SubmissionObserver
{
    public function created(N9Submission $submission): void
    {
        $this->sync($submission);
    }

    public function updated(N9Submission $submission): void
    {
        $this->sync($submission);
    }

    protected function sync(N9Submission $submission): void
    {
        if (empty($submission->file_path)) {
            return;
        }

        $exists = MailItem::where('source_type', 'n9_submission')
            ->where('source_id', $submission->id)
            ->exists();

        if ($exists) {
            return;
        }

        [$line1, $line2, $city, $prov, $postal] = FormSubmissionObserver::parseAddress($submission->unit_address);

        MailItem::create([
            'source_type' => 'n9_submission',
            'source_id' => $submission->id,
            'tenant_id' => $submission->tenant_id,
            'property_id' => $submission->property_id,
            'form_label' => 'N9',
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
}
