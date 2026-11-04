<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Invoice;
use App\Models\MailItem;
use App\Models\MaintenanceTicket;
use App\Models\N9Submission;
use App\Models\OwnerFile;
use App\Models\OwnerInvoice;
use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\TenantFile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Authenticated delivery for tenant/owner documents.
 *
 * Everything in storage/app/public used to be served directly by nginx, which
 * exposed rental applications (government ID, proof of employment), tenant and
 * owner files, N9 submissions and invoice PDFs to anyone who could guess a path.
 * nginx now refuses /storage/* except marketing assets; these routes are the only
 * way to fetch a document, and every one of them checks the viewer first.
 */
class SecureFileController extends Controller
{
    private function viewer(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403, 'Please sign in to download this file.');

        return $user;
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager'], true);
    }

    private function ownsProperty(User $user, ?int $propertyId): bool
    {
        if (! $propertyId) {
            return false;
        }

        return Property::query()
            ->where('id', $propertyId)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)->orWhere('co_owner_id', $user->id);
            })
            ->exists();
    }

    /** Stream a file from the storage disk. Paths are relative (e.g. tenant-files/x.pdf). */
    private function stream(?string $path)
    {
        $path = ltrim((string) $path, '/');
        abort_if($path === '' || str_contains($path, '..'), 404);

        // Documents live on the private `documents` disk; `public` is only a fallback
        // for shared, non-PII assets (forms) and anything not yet migrated.
        foreach (['documents', 'public'] as $disk) {
            $absolute = Storage::disk($disk)->path($path);
            if (is_file($absolute)) {
                return response()->file($absolute, [
                    'X-Content-Type-Options' => 'nosniff',
                    'Cache-Control' => 'private, no-store, max-age=0',
                    'Content-Disposition' => 'inline; filename="'.basename($absolute).'"',
                ]);
            }
        }

        abort(404);
    }

    /** Short-lived signed link for files that have no model yet (generated letters). */
    public function signed(string $encoded)
    {
        $path = base64_decode(strtr($encoded, '-_', '+/'), true);
        abort_if($path === false || str_contains($path, '..'), 404);

        return $this->stream($path);
    }

    public function tenantFile(TenantFile $tenantFile)
    {
        $user = $this->viewer();

        $ownerOfUnit = $tenantFile->propertyUnit
            ? $this->ownsProperty($user, $tenantFile->propertyUnit->property_id)
            : false;

        abort_unless(
            $this->isAdmin($user) || $tenantFile->tenant_id === $user->id || $ownerOfUnit,
            403
        );

        return $this->stream($tenantFile->file_path);
    }

    public function ownerFile(OwnerFile $ownerFile)
    {
        $user = $this->viewer();

        abort_unless($this->isAdmin($user) || $ownerFile->owner_id === $user->id, 403);

        return $this->stream($ownerFile->file_path);
    }

    public function n9(N9Submission $n9Submission)
    {
        $user = $this->viewer();

        abort_unless(
            $this->isAdmin($user)
            || $n9Submission->tenant_id === $user->id
            || $n9Submission->owner_id === $user->id
            || $this->ownsProperty($user, $n9Submission->property_id),
            403
        );

        return $this->stream($n9Submission->file_path);
    }

    public function ticketInvoice(MaintenanceTicket $ticket)
    {
        $user = $this->viewer();

        abort_unless(
            $this->isAdmin($user)
            || $ticket->tenant_id === $user->id
            || $this->ownsProperty($user, $ticket->property_id),
            403
        );

        return $this->stream($ticket->invoice_path);
    }

    public function ticketPhoto(MaintenanceTicket $ticket, int $index)
    {
        $user = $this->viewer();

        abort_unless(
            $this->isAdmin($user)
            || $ticket->tenant_id === $user->id
            || $this->ownsProperty($user, $ticket->property_id),
            403
        );

        $photos = is_array($ticket->photos) ? array_values($ticket->photos) : [];
        abort_unless(array_key_exists($index, $photos), 404);

        return $this->stream($photos[$index]);
    }

    public function invoice(Invoice $invoice)
    {
        $user = $this->viewer();

        abort_unless(
            $this->isAdmin($user)
            || $invoice->tenant_id === $user->id
            || $this->ownsProperty($user, $invoice->property_id),
            403
        );

        return $this->stream($invoice->file_path);
    }

    public function ownerInvoice(OwnerInvoice $ownerInvoice)
    {
        $user = $this->viewer();

        abort_unless($this->isAdmin($user) || $ownerInvoice->owner_id === $user->id, 403);

        return $this->stream($ownerInvoice->file_path);
    }

    public function form(Form $form)
    {
        $this->viewer(); // any signed-in user may read shared forms

        return $this->stream($form->file_path);
    }

    public function formSubmission(FormSubmission $formSubmission)
    {
        $user = $this->viewer();

        abort_unless($this->isAdmin($user) || $formSubmission->tenant_id === $user->id, 403);

        return $this->stream($formSubmission->file_path);
    }

    public function mailItem(MailItem $mailItem)
    {
        $user = $this->viewer();

        abort_unless(
            $this->isAdmin($user)
            || $mailItem->tenant_id === $user->id
            || $this->ownsProperty($user, $mailItem->property_id),
            403
        );

        return $this->stream($mailItem->pdf_path);
    }

    public function application(RentalApplication $rentalApplication, string $field)
    {
        $user = $this->viewer();

        abort_unless(in_array($field, ['gov_id_path', 'proof_of_employment_path'], true), 404);

        $propertyId = $rentalApplication->propertyUnit->property_id ?? null;

        abort_unless(
            $this->isAdmin($user)
            || $this->ownsProperty($user, $propertyId)
            || ($rentalApplication->email && $rentalApplication->email === $user->email),
            403
        );

        return $this->stream($rentalApplication->{$field});
    }
}
