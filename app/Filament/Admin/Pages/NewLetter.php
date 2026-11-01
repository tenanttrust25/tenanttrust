<?php

namespace App\Filament\Admin\Pages;

use App\Models\MailItem;
use App\Models\User;
use App\Services\LetterPdfService;
use App\Services\PostGridService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class NewLetter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Mail';

    protected static ?string $navigationLabel = 'New Letter';

    protected static ?string $title = 'New Letter';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.admin.new-letter';

    public ?int $tenant_id = null;

    public string $recipient_name = '';

    public string $address_line1 = '';

    public string $address_line2 = '';

    public string $city = '';

    public string $province = 'ON';

    public string $postal_code = '';

    public string $subject = '';

    public string $salutation = '';

    public string $body = '';

    public string $signer_name = 'Steve Ouimette';

    public string $form_label = 'Letter';

    public bool $send_now = false;

    public ?string $last_pdf = null;

    public function tenants()
    {
        return User::where('role', 'tenant')->orderBy('name')->get(['id', 'name']);
    }

    public function updatedTenantId($value): void
    {
        if (! $value) {
            return;
        }

        $tenant = User::find($value);
        if (! $tenant) {
            return;
        }

        $this->recipient_name = (string) $tenant->name;

        [$line1, $line2, $city, $prov, $postal] = self::parseAddress(
            $tenant->mailing_address ?: $tenant->address
        );

        $this->address_line1 = (string) $line1;
        $this->address_line2 = (string) $line2;
        $this->city = (string) $city;
        $this->province = $prov ?: 'ON';
        $this->postal_code = (string) $postal;

        if ($this->salutation === '') {
            $this->salutation = 'Dear ' . explode(' ', trim((string) $tenant->name))[0] . ',';
        }
    }

    /**
     * Parse "473 Montague Ave, Unit 2, Sudbury, P3C 4G6"
     * into [street, unit, city, province, postal].
     *
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string, 4: ?string}
     */
    public static function parseAddress(?string $raw): array
    {
        return \App\Support\AddressParser::parse($raw);
    }

    public function generate(): void
    {
        $this->validate([
            'recipient_name' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'body' => 'required|string',
            'form_label' => 'nullable|string|max:100',
        ]);

        $path = app(LetterPdfService::class)->generate([
            'recipient_name' => $this->recipient_name,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2 ?: null,
            'city' => $this->city ?: null,
            'province' => $this->province ?: 'ON',
            'postal_code' => $this->postal_code ?: null,
            'subject' => $this->subject ?: null,
            'salutation' => $this->salutation ?: null,
            'body' => $this->body,
            'signer_name' => $this->signer_name ?: 'Steve Ouimette',
        ]);

        $item = MailItem::create([
            'source_type' => 'letterhead',
            'tenant_id' => $this->tenant_id,
            'form_label' => $this->form_label ?: 'Letter',
            'recipient_name' => $this->recipient_name,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2 ?: null,
            'city' => $this->city ?: null,
            'province' => $this->province ?: 'ON',
            'postal_code' => $this->postal_code ?: null,
            'country' => 'CA',
            'single_sheet' => true,
            'pdf_path' => $path,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        $this->last_pdf = $path;

        if ($this->send_now) {
            try {
                app(PostGridService::class)->send($item, \App\Support\TenantTrustSettings::postgridMode());
                Notification::make()
                    ->title('Letter drafted + sent to PostGrid')
                    ->body('PostGrid ID: ' . ($item->fresh()->postgrid_id ?: '—'))
                    ->success()->send();
            } catch (Throwable $e) {
                Notification::make()->title('Draft saved, but send failed')->body($e->getMessage())->warning()->send();
            }
        } else {
            Notification::make()
                ->title('Letter drafted')
                ->body('Added to Outgoing Mail — press “Send Letter Mail” when ready.')
                ->success()->send();
        }

        $this->reset(['body', 'subject']);
    }
}
