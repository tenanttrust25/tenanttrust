<?php

namespace App\Services;

use App\Models\Form;
use App\Models\Tenancy;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Generic LTB form filling. Each supported form category (n1, n4, n5, n11, n9)
 * has a field map: Filament schema for the admin modal, a values builder that
 * turns the submitted data into PDF widget values, and per-widget font styles.
 */
class FormFillService
{
    /** Default signer for landlord-signed notices: Tenant Trust (Steve) as representative. */
    protected const SIGNER_ROLE = 'representative';
    protected const SIGNER_FIRST = 'Steve';
    protected const SIGNER_LAST = 'Ouimette (Property Manager)';
    protected const SIGNER_PHONE = '249-523-3331';
    protected const SIGNER_NAME = 'Steve Ouimette (Property Manager)';
    protected const SIGNER_SIGNATURE = 'Steve Ouimette';

    /** Tenant Trust (the manager) — default representative details on notices. */
    protected const MANAGER_COMPANY = 'Tenant Trust';
    protected const MANAGER_EMAIL = 'info@tenanttrust.ca';

    public function categories(): array
    {
        return [
            'n1' => 'N1 — Notice of Rent Increase',
            'n4' => 'N4 — Notice to End Tenancy Early (Non-payment of Rent)',
            'n5' => 'N5 — Notice to End Tenancy Early (Interference / Damage)',
            'n11' => 'N11 — Agreement to End the Tenancy',
            'n9' => 'N9 — Tenant\'s Notice to End the Tenancy',
        ];
    }

    public function categoryFor(Form $form): ?string
    {
        $name = strtoupper($form->name);
        foreach (['N11' => 'n11', 'N9' => 'n9', 'N5' => 'n5', 'N4' => 'n4', 'N1' => 'n1'] as $needle => $cat) {
            if (str_contains($name, $needle)) {
                return $cat;
            }
        }

        return null;
    }

    public function options(): array
    {
        return Form::all()
            ->filter(fn (Form $f): bool => $this->categoryFor($f) !== null)
            ->mapWithKeys(fn (Form $f): array => [$f->id => $this->categories()[$this->categoryFor($f)]])
            ->toArray();
    }

    /** Prefill context derived from the tenant record. */
    public function prefill(User $tenant): array
    {
        $tenancy = $tenant->activeTenancies()->with(['property.owner', 'propertyUnit'])->first();
        $property = $tenancy?->property;
        $owner = $property?->owner;
        $unit = $tenancy?->propertyUnit;

        $tenantNames = $tenant->name;
        if ($unit) {
            $names = Tenancy::where('property_unit_id', $unit->id)
                ->where('status', 'active')
                ->with('tenant')
                ->get()
                ->pluck('tenant.name')
                ->filter()
                ->unique();
            if ($names->isNotEmpty()) {
                $tenantNames = $names->implode(' & ');
            }
        }

        $unitAddress = trim(collect([$property?->address, $property?->city, $property?->province, $property?->postal_code])
            ->filter()
            ->implode(', '), ', ');
        if ($unit?->unit) {
            $unitAddress .= ' — Unit ' . $unit->unit;
        }

        // Structured unit-address parts used by the redesigned N4 (2026/09).
        $streetNumber = null;
        $streetName = trim((string) $property?->address);
        if ($streetName !== '' && preg_match('/^\s*(\d+[A-Za-z]?)\s+(.*)$/', $streetName, $m)) {
            $streetNumber = $m[1];
            $streetName = trim($m[2]);
        }
        $unitLabel = null;
        if ($unit?->unit) {
            $rawUnit = trim((string) $unit->unit);
            $unitLabel = preg_match('/^\d+[A-Za-z]?$/', $rawUnit) ? 'Unit ' . $rawUnit : $rawUnit;
        }

        [$tFirst, $tLast] = $this->splitName($tenant->name);
        [$oFirst, $oLast] = $this->splitName($owner?->name ?? '');

        return [
            'tenant_name' => $tenant->name,
            'tenant_names' => $tenantNames,
            'tenant_first' => $tFirst,
            'tenant_last' => $tLast,
            'tenant_phone' => $tenant->phone ?? '',
            'tenant_address' => $tenant->address ?: $unitAddress,
            'landlord_name' => $owner?->name ?? '',
            'landlord_first' => $oFirst,
            'landlord_last' => $oLast,
            'landlord_phone' => $owner?->phone ?? '',
            'landlord_address' => ($owner?->address ?: $property?->address) ?? '',
            'unit_address' => $unitAddress,
            'unit_street_number' => $streetNumber,
            'unit_street_name' => $streetName ?: null,
            'unit_number' => $unitLabel,
            'unit_city' => $property?->city,
            'unit_province' => strtoupper(substr(trim((string) ($property?->province ?: 'ON')), 0, 2)) ?: 'ON',
            'unit_postal' => $property?->postal_code,
            'manager_company' => self::MANAGER_COMPANY,
            'manager_email' => self::MANAGER_EMAIL,
            'manager_street_number' => '474',
            'manager_street_name' => 'McKim St',
            'manager_unit' => 'Unit 3',
            'manager_city' => 'Sudbury',
            'manager_province' => 'ON',
            'manager_postal' => 'P3C 2L6',
            'property_id' => $property?->id,
            'property_unit_id' => $unit?->id,
            'owner_id' => $owner?->id,
            'today' => now(),
        ];
    }

    /** Filament schema for the modal, prefilled from $ctx. */
    public function schema(string $category, array $ctx): array
    {
        return match ($category) {
            'n1' => $this->n1Schema($ctx),
            'n4' => $this->n4Schema($ctx),
            'n5' => $this->n5Schema($ctx),
            'n11' => $this->n11Schema($ctx),
            'n9' => $this->n9Schema($ctx),
            default => [],
        };
    }

    /**
     * Prefill values (field-name => value) for a category, used to hydrate the
     * modal form state when the template is selected. Filament v3 only applies
     * ->default() to fields mounted at form creation, so dynamically added
     * fields need their state set explicitly.
     */
    public function prefillData(string $category, array $ctx): array
    {
        $today = $ctx['today']->toDateString();

        return match ($category) {
            'n1' => [
                'n1_tenant_name_address' => $ctx['tenant_names'],
                'n1_landlord_name_address' => $ctx['landlord_name'],
                'n1_unit_address' => $ctx['unit_address'],
                'n1_on_date' => $this->n1EffectiveDate($ctx['today'])->toDateString(),
                'n1_period' => 'month',
                'n1_increase_period' => 'month',
                'n1_guideline' => 'less_equal',
                'n1_signer' => self::SIGNER_ROLE,
                'n1_signer_name' => self::SIGNER_NAME,
                'n1_signer_phone' => self::SIGNER_PHONE,
                'n1_signature' => self::SIGNER_SIGNATURE,
                'n1_sign_date' => $today,
            ],
            'n4' => [
                'n4_tenant_name' => $ctx['tenant_names'],
                'n4_landlord_name' => $ctx['landlord_name'],
                'n4_amount_owing' => null,
                'n4_pay_date' => $today,
                'n4_street_number' => $ctx['unit_street_number'] ?? '',
                'n4_street_name' => $ctx['unit_street_name'] ?? '',
                'n4_unit' => $ctx['unit_number'] ?? '',
                'n4_city' => $ctx['unit_city'] ?? '',
                'n4_province' => $ctx['unit_province'] ?? 'ON',
                'n4_postal_code' => $ctx['unit_postal'] ?? '',
                'n4_signer' => self::SIGNER_ROLE,
                'n4_signer_first_name' => self::SIGNER_FIRST,
                'n4_signer_last_name' => self::SIGNER_LAST,
                'n4_signer_phone' => self::SIGNER_PHONE,
                'n4_signature' => self::SIGNER_SIGNATURE,
                'n4_sign_date' => $today,
                'n4_rep_first_name' => self::SIGNER_FIRST,
                'n4_rep_last_name' => self::SIGNER_LAST,
                'n4_rep_lso' => '',
                'n4_rep_company' => $ctx['manager_company'] ?? self::MANAGER_COMPANY,
                'n4_rep_street_number' => $ctx['manager_street_number'] ?? '',
                'n4_rep_street_name' => $ctx['manager_street_name'] ?? '',
                'n4_rep_unit' => $ctx['manager_unit'] ?? '',
                'n4_rep_city' => $ctx['manager_city'] ?? '',
                'n4_rep_province' => $ctx['manager_province'] ?? 'ON',
                'n4_rep_postal_code' => $ctx['manager_postal'] ?? '',
                'n4_rep_email' => $ctx['manager_email'] ?? self::MANAGER_EMAIL,
                'n4_rep_phone' => self::SIGNER_PHONE,
            ],
            'n5' => [
                'n5_tenant_name' => $ctx['tenant_names'],
                'n5_landlord_name' => $ctx['landlord_name'],
                'n5_street_number' => $ctx['unit_street_number'] ?? '',
                'n5_street_name' => $ctx['unit_street_name'] ?? '',
                'n5_unit' => $ctx['unit_number'] ?? '',
                'n5_city' => $ctx['unit_city'] ?? '',
                'n5_province' => $ctx['unit_province'] ?? 'ON',
                'n5_postal_code' => $ctx['unit_postal'] ?? '',
                'n5_termination_date' => $today,
                'n5_reason1_option' => 'first',
                'n5_reason2_option' => 'first',
                'n5_reason3_option' => 'first',
                'n5_signer' => self::SIGNER_ROLE,
                'n5_signer_first_name' => self::SIGNER_FIRST,
                'n5_signer_last_name' => self::SIGNER_LAST,
                'n5_signer_phone' => self::SIGNER_PHONE,
                'n5_signature' => self::SIGNER_SIGNATURE,
                'n5_sign_date' => $today,
                'n5_rep_first_name' => self::SIGNER_FIRST,
                'n5_rep_last_name' => self::SIGNER_LAST,
                'n5_rep_lso' => '',
                'n5_rep_company' => $ctx['manager_company'] ?? self::MANAGER_COMPANY,
                'n5_rep_street_number' => $ctx['manager_street_number'] ?? '',
                'n5_rep_street_name' => $ctx['manager_street_name'] ?? '',
                'n5_rep_unit' => $ctx['manager_unit'] ?? '',
                'n5_rep_city' => $ctx['manager_city'] ?? '',
                'n5_rep_province' => $ctx['manager_province'] ?? 'ON',
                'n5_rep_postal_code' => $ctx['manager_postal'] ?? '',
                'n5_rep_email' => $ctx['manager_email'] ?? self::MANAGER_EMAIL,
                'n5_rep_phone' => self::SIGNER_PHONE,
            ],
            'n11' => [
                'n11_tenant_names' => $ctx['tenant_names'],
                'n11_landlord_name' => $ctx['landlord_name'],
                'n11_unit_address' => $ctx['unit_address'],
                'n11_termination_date' => $today,
                'n11_tenant_first_name' => $ctx['tenant_first'],
                'n11_tenant_last_name' => $ctx['tenant_last'],
                'n11_tenant_phone' => $ctx['tenant_phone'],
                'n11_landlord_first_name' => self::SIGNER_FIRST,
                'n11_landlord_last_name' => self::SIGNER_LAST,
                'n11_landlord_phone' => self::SIGNER_PHONE,
                'n11_sign_date' => $today,
            ],
            'n9' => [
                'n9_tenant_names' => $ctx['tenant_names'],
                'n9_landlord_name' => $ctx['landlord_name'],
                'n9_unit_address' => $ctx['unit_address'],
                'n9_termination_date' => $today,
                'n9_tenant_first_name' => $ctx['tenant_first'],
                'n9_tenant_last_name' => $ctx['tenant_last'],
                'n9_tenant_phone' => $ctx['tenant_phone'],
                'n9_signer' => 'tenant',
                'n9_sign_date' => $today,
            ],
            default => [],
        };
    }

    /** Map submitted modal data to PDF widget values. */
    public function buildValues(string $category, array $data, array $ctx): array
    {
        return match ($category) {
            'n1' => $this->n1Values($data, $ctx),
            'n4' => $this->n4Values($data, $ctx),
            'n5' => $this->n5Values($data, $ctx),
            'n11' => $this->n11Values($data, $ctx),
            'n9' => $this->n9Values($data, $ctx),
            default => [],
        };
    }

    public function styles(string $category): array
    {
        return match ($category) {
            'n1' => ['Date ddmmyyyy' => ['helv', 12], 'Signature' => ['helv', 12]],
            'n5' => [
                'form1[0].sfrmForm[0].Signature[0].Signature[0]' => ['helv', 12],
                'form1[0].sfrmForm[0].Signature[0].dptSignDate[0]' => ['helv', 12],
            ],
            'n4' => [
                'form1[0].sfrmForm[0].Signature[0].Signature[0]' => ['helv', 12],
                'form1[0].sfrmForm[0].Signature[0].dptSignDate[0]' => ['helv', 12],
            ],
            'n11' => ['Date ddmmyyyy_2' => ['helv', 12]],
            'n9' => ['Date ddmmyyyy' => ['helv', 12]],
            default => [],
        };
    }

    public function generate(Form $form, string $category, array $values, array $styles): string
    {
        $template = $this->templatePath($form, $category);

        $outputDir = storage_path('app/public/form-submissions');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $filename = strtoupper($category) . '_' . now()->format('Ymd_His') . '_' . Str::random(4) . '.pdf';
        $output = $outputDir . '/' . $filename;

        // Values may contain malformed UTF-8 copied from user records (e.g.
        // Windows-1252 curly quotes/em dashes). Sanitize so json_encode can
        // never return false and break the Python bridge.
        $valuesJson = json_encode($this->utf8($values), JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        $stylesJson = json_encode($styles, JSON_INVALID_UTF8_SUBSTITUTE);

        $process = new Process([
            'python3',
            base_path('scripts/fill_form.py'),
            $template,
            $output,
            $valuesJson ?: '{}',
            $stylesJson ?: '{}',
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Form PDF generation failed: ' . $process->getErrorOutput());
        }

        return 'form-submissions/' . $filename;
    }

    /** Recursively convert strings to valid UTF-8, replacing malformed bytes. */
    protected function utf8(mixed $value): mixed
    {
        if (is_string($value)) {
            if (mb_check_encoding($value, 'UTF-8')) {
                return $value;
            }

            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->utf8($v);
            }
        }

        return $value;
    }

    protected function templatePath(Form $form, string $category): string
    {
        if ($category === 'n9') {
            $private = storage_path('app/private/n9-template.pdf');
            if (file_exists($private)) {
                return $private;
            }
        }

        return storage_path('app/public/' . $form->file_path);
    }

    // ------------------------------------------------------------------
    // N1 — Notice of Rent Increase
    // ------------------------------------------------------------------

    /** N1 rent-increase effective date: 1st of a month, at least 90 days out. */
    protected function n1EffectiveDate(Carbon $today): Carbon
    {
        $effective = $today->copy()->addMonthsNoOverflow(3)->startOfMonth();

        // If +3 months snapped back before the 90-day minimum, roll to the next 1st.
        if ($effective->lt($today->copy()->addDays(90))) {
            $effective->addMonthNoOverflow();
        }

        return $effective;
    }

    protected function n1Schema(array $ctx): array
    {
        return [
            Textarea::make('n1_tenant_name_address')->label("Tenant's Name")
                ->rows(2)->default($ctx['tenant_names'])->columnSpanFull(),
            Textarea::make('n1_landlord_name_address')->label("Landlord's Name")
                ->rows(2)->default($ctx['landlord_name'])->columnSpanFull(),
            TextInput::make('n1_unit_address')->label('Address of the Rental Unit')
                ->default($ctx['unit_address'])->columnSpanFull(),
            DatePicker::make('n1_on_date')->label('Rent increase effective date (On…)')
                ->native(false)->displayFormat('M d, Y')->default($this->n1EffectiveDate(Carbon::parse($ctx['today']))),
            TextInput::make('n1_new_rent_amount')->label('New rent amount ($)')->numeric(),
            Select::make('n1_period')->label('New rent period')->options(['month' => 'per month', 'week' => 'per week', 'other' => 'other (specify)'])
                ->default('month')->live(),
            TextInput::make('n1_period_other')->label('Other period — specify')
                ->visible(fn (Get $get): bool => $get('n1_period') === 'other'),
            TextInput::make('n1_increase_amount')->label('Rent increase amount ($)')->numeric(),
            TextInput::make('n1_increase_percent')->label('Rent increase (%)')->numeric(),
            Select::make('n1_increase_period')->label('Increase period')->options(['month' => 'per month', 'week' => 'per week', 'other' => 'other (specify)'])
                ->default('month')->live(),
            TextInput::make('n1_increase_other')->label('Other period — specify')
                ->visible(fn (Get $get): bool => $get('n1_increase_period') === 'other'),
            Select::make('n1_guideline')->label('Guideline option')
                ->options([
                    'less_equal' => 'At or below the guideline — no approval needed',
                    'more_approved' => 'Above the guideline — already approved by Board order',
                    'more_applied' => 'Above the guideline — application filed with the Board',
                ])->default('less_equal')->columnSpanFull(),
            Select::make('n1_signer')->label('Signing as')->options(['landlord' => 'Landlord', 'representative' => 'Representative'])->default(self::SIGNER_ROLE),
            TextInput::make('n1_signer_name')->label('Name of person signing')->default(self::SIGNER_NAME),
            TextInput::make('n1_signer_phone')->label('Phone number')->default(self::SIGNER_PHONE),
            TextInput::make('n1_signature')->label('Signature (typed name)')->default(self::SIGNER_SIGNATURE),
            DatePicker::make('n1_sign_date')->label('Date signed')->native(false)->displayFormat('M d, Y')->default($ctx['today']),
        ];
    }

    protected function n1Values(array $data, array $ctx): array
    {
        $on = Carbon::parse($data['n1_on_date'] ?? $ctx['today']);
        $sign = Carbon::parse($data['n1_sign_date'] ?? $ctx['today']);
        $period = $data['n1_period'] ?? 'month';
        $incPeriod = $data['n1_increase_period'] ?? 'month';
        $guideline = $data['n1_guideline'] ?? 'less_equal';
        $signer = $data['n1_signer'] ?? self::SIGNER_ROLE;

        return [
            'To Tenants name and addressRow1' => $data['n1_tenant_name_address'] ?? null,
            'From Landlords name and addressRow1' => $data['n1_landlord_name_address'] ?? null,
            'Address of the Rental UnitRow1' => $data['n1_unit_address'] ?? null,
            'undefined' => $on->format('d'),
            'Text1' => $on->format('m'),
            'ddmmyyyy' => $on->format('Y'),
            'undefined_2' => $data['n1_new_rent_amount'] ?? null,
            'month' => $period === 'month' ? 'X' : null,
            'week' => $period === 'week' ? 'X' : null,
            'other specify' => $period === 'other' ? 'X' : null,
            'undefined_4' => $period === 'other' ? ($data['n1_period_other'] ?? null) : null,
            'undefined_5' => $data['n1_increase_amount'] ?? null,
            'month_2' => $incPeriod === 'month' ? 'X' : null,
            'week_2' => $incPeriod === 'week' ? 'X' : null,
            'other specify_2' => $incPeriod === 'other' ? 'X' : null,
            'or' => $incPeriod === 'other' ? ($data['n1_increase_other'] ?? null) : null,
            'undefined_7' => $data['n1_increase_percent'] ?? null,
            'This rent increase is less than or equal to the rent increase guideline and does not' => $guideline === 'less_equal' ? 'X' : null,
            'This rent increase is more than the rent increase guideline but' => in_array($guideline, ['more_approved', 'more_applied']) ? 'X' : null,
            'The rent increase has been approved by an order under the Residential' => $guideline === 'more_approved' ? 'X' : null,
            'The rent increase must be approved by an order under the Residential' => $guideline === 'more_applied' ? 'X' : null,
            'Landlord' => $signer === 'landlord' ? 'X' : null,
            'Representative' => $signer === 'representative' ? 'X' : null,
            'Name of Person Signing' => $data['n1_signer_name'] ?? null,
            'Phone Number' => $data['n1_signer_phone'] ?? null,
            'Signature' => $data['n1_signature'] ?? null,
            'Date ddmmyyyy' => $sign->format('d/m/Y'),
        ];
    }

    // ------------------------------------------------------------------
    // N4 — Notice to End Tenancy Early (Non-payment of Rent)
    // ------------------------------------------------------------------

    protected function n4Schema(array $ctx): array
    {
        return [
            TextInput::make('n4_tenant_name')->label("Tenant's name (include all tenant names)")
                ->default($ctx['tenant_names'])->columnSpanFull(),
            TextInput::make('n4_landlord_name')->label("Landlord's name")
                ->default($ctx['landlord_name'])->columnSpanFull(),
            TextInput::make('n4_amount_owing')->label('Amount of rent owing ($)')
                ->numeric()
                ->helperText('The box on page 1. Must equal the Total Rent Owing in the table below (it is auto-filled from the table if you leave it blank).'),
            DatePicker::make('n4_pay_date')->label('Termination date — pay or move out by (dd/mm/yyyy)')
                ->native(false)->displayFormat('M d, Y')->default($ctx['today'])
                ->helperText('At least 7 days after the notice is served — add extra days if delivered by mail or courier.'),

            \Filament\Forms\Components\Section::make('Address of the rental unit')
                ->columns(6)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('n4_street_number')->label('Street number')->columnSpan(2)
                        ->default($ctx['unit_street_number'] ?? ''),
                    TextInput::make('n4_street_name')->label('Street name')->columnSpan(4)
                        ->default($ctx['unit_street_name'] ?? ''),
                    TextInput::make('n4_unit')->label('Unit/Apt./Suite')->columnSpan(2)
                        ->default($ctx['unit_number'] ?? ''),
                    TextInput::make('n4_city')->label('Municipality (city, town, etc.)')->columnSpan(2)
                        ->default($ctx['unit_city'] ?? ''),
                    TextInput::make('n4_province')->label('Province')->columnSpan(1)
                        ->default($ctx['unit_province'] ?? 'ON'),
                    TextInput::make('n4_postal_code')->label('Postal code')->columnSpan(1)
                        ->default($ctx['unit_postal'] ?? ''),
                ]),

            Repeater::make('n4_arrears')->label('Rent owing — how the total is calculated')
                ->schema([
                    DatePicker::make('from')->label('Period from')->native(false)->displayFormat('M d, Y'),
                    DatePicker::make('to')->label('Period to')->native(false)->displayFormat('M d, Y'),
                    TextInput::make('charged')->label('Rent charged ($)')->numeric(),
                    TextInput::make('paid')->label('Rent paid ($)')->numeric(),
                ])
                ->columns(4)->maxItems(3)->defaultItems(0)->columnSpanFull()
                ->helperText('Rent owing is calculated per row (charged − paid); the Total Rent Owing fills in automatically.'),

            \Filament\Forms\Components\Section::make('Signature')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('n4_signer')->label('Signing as')
                        ->options(['landlord' => 'Landlord', 'representative' => 'Representative'])
                        ->default(self::SIGNER_ROLE)->live()->columnSpanFull(),
                    TextInput::make('n4_signer_first_name')->label('First name')->default(self::SIGNER_FIRST),
                    TextInput::make('n4_signer_last_name')->label('Last name')->default(self::SIGNER_LAST),
                    TextInput::make('n4_signer_phone')->label('Phone number')->default(self::SIGNER_PHONE)->columnSpanFull(),
                    TextInput::make('n4_signature')->label('Signature (typed name)')->default(self::SIGNER_SIGNATURE),
                    DatePicker::make('n4_sign_date')->label('Date signed (dd/mm/yyyy)')->native(false)->displayFormat('M d, Y')->default($ctx['today']),
                ]),

            \Filament\Forms\Components\Section::make('Representative information')
                ->description('Completed only when the notice is signed by a representative.')
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('n4_signer') === 'representative')
                ->schema([
                    TextInput::make('n4_rep_first_name')->label('First name')->default(self::SIGNER_FIRST),
                    TextInput::make('n4_rep_last_name')->label('Last name')->default(self::SIGNER_LAST),
                    TextInput::make('n4_rep_lso')->label('LSO number (if applicable)'),
                    TextInput::make('n4_rep_company')->label('Company name (if applicable)')
                        ->default($ctx['manager_company'] ?? self::MANAGER_COMPANY),
                    TextInput::make('n4_rep_street_number')->label('Street number')
                        ->default($ctx['manager_street_number'] ?? ''),
                    TextInput::make('n4_rep_street_name')->label('Street name')
                        ->default($ctx['manager_street_name'] ?? ''),
                    TextInput::make('n4_rep_unit')->label('Unit/Apt./Suite')
                        ->default($ctx['manager_unit'] ?? ''),
                    TextInput::make('n4_rep_city')->label('Municipality')
                        ->default($ctx['manager_city'] ?? ''),
                    TextInput::make('n4_rep_province')->label('Province')
                        ->default($ctx['manager_province'] ?? 'ON'),
                    TextInput::make('n4_rep_postal_code')->label('Postal code')
                        ->default($ctx['manager_postal'] ?? ''),
                    TextInput::make('n4_rep_email')->label('Email address')
                        ->default($ctx['manager_email'] ?? self::MANAGER_EMAIL),
                    TextInput::make('n4_rep_phone')->label('Phone number')->default(self::SIGNER_PHONE),
                ]),
        ];
    }

    protected function n4Values(array $data, array $ctx): array
    {
        $sign = Carbon::parse($data['n4_sign_date'] ?? $ctx['today']);
        $pay = ! empty($data['n4_pay_date']) ? Carbon::parse($data['n4_pay_date']) : null;
        $signer = $data['n4_signer'] ?? self::SIGNER_ROLE;

        // The on-disk template is the redesigned LTB N4 (2026/09). Its widgets are
        // nested and use different names from the old N4, so map by the full
        // widget path — the short-name fallback in fill_form.py can collide
        // (e.g. txtNumber/txtCity exist in both the unit-address and rep blocks).
        $values = [
            // Page 1 (notice): amount owing, termination date, parties, unit address
            'form1[0].sfrmForm[0].InfofromLandlord1[0].dptPayDate[0]' => $pay?->format('d/m/Y'),
            'form1[0].sfrmForm[0].PartyAddressInfo[0].txtTenant[0]' => $data['n4_tenant_name'] ?? null,
            'form1[0].sfrmForm[0].PartyAddressInfo[0].txtLandlord[0]' => $data['n4_landlord_name'] ?? null,
            'form1[0].sfrmForm[0].PartyAddressInfo[0].txtNumber[0]' => $data['n4_street_number'] ?? null,
            'form1[0].sfrmForm[0].PartyAddressInfo[0].txtName[0]' => $data['n4_street_name'] ?? null,
            'form1[0].sfrmForm[0].PartyAddressInfo[0].txtUnit[0]' => $data['n4_unit'] ?? null,
            'form1[0].sfrmForm[0].PartyAddressInfo[0].txtCity[0]' => $data['n4_city'] ?? null,
            'form1[0].sfrmForm[0].PartyAddressInfo[0].txtProvince[0]' => $data['n4_province'] ?: 'ON',
            'form1[0].sfrmForm[0].PartyAddressInfo[0].txtPostalCode[0]' => $data['n4_postal_code'] ?? null,

            // Signature block
            'form1[0].sfrmForm[0].Signature[0].PartyType[0]#0' => $signer === 'landlord' ? 'X' : null,
            'form1[0].sfrmForm[0].Signature[0].PartyType[0]#1' => $signer === 'representative' ? 'X' : null,
            'form1[0].sfrmForm[0].Signature[0].txtFirstName[0]' => $data['n4_signer_first_name'] ?? null,
            'form1[0].sfrmForm[0].Signature[0].txtLastName[0]' => $data['n4_signer_last_name'] ?? null,
            'form1[0].sfrmForm[0].Signature[0].txtPhone1[0]' => $data['n4_signer_phone'] ?? null,
            'form1[0].sfrmForm[0].Signature[0].Signature[0]' => $data['n4_signature'] ?? null,
            'form1[0].sfrmForm[0].Signature[0].dptSignDate[0]' => $sign->format('d/m/Y'),
        ];

        if ($signer === 'representative') {
            $rep = 'form1[0].sfrmForm[0].LegalRepInfo[0].sfrmLegalRepInfo_1[0].';
            $values[$rep . 'txtFirstName[0]'] = $data['n4_rep_first_name'] ?? null;
            $values[$rep . 'txtLastName[0]'] = $data['n4_rep_last_name'] ?? null;
            $values[$rep . 'txtLSOnumber[0]'] = $data['n4_rep_lso'] ?? null;
            $values[$rep . 'txtCompanyName[0]'] = $data['n4_rep_company'] ?? null;
            $values[$rep . 'txtNumber[0]'] = $data['n4_rep_street_number'] ?? null;
            $values[$rep . 'txtName[0]'] = $data['n4_rep_street_name'] ?? null;
            $values[$rep . 'txtUnit[0]'] = $data['n4_rep_unit'] ?? null;
            $values[$rep . 'txtCity[0]'] = $data['n4_rep_city'] ?? null;
            $values[$rep . 'txtProvince[0]'] = $data['n4_rep_province'] ?: 'ON';
            $values[$rep . 'txtPostalCode[0]'] = $data['n4_rep_postal_code'] ?? null;
            $values[$rep . 'txtEmail[0]'] = $data['n4_rep_email'] ?? null;
            $values[$rep . 'txtPhone1[0]'] = $data['n4_rep_phone'] ?? null;
        }

        // Rent-owing table + Total Rent Owing
        $total = 0.0;
        $rows = $data['n4_arrears'] ?? [];
        foreach (range(1, 3) as $i) {
            $row = $rows[$i - 1] ?? null;
            if (! $row || blank($row['charged'] ?? null)) {
                continue;
            }
            $charged = (float) ($row['charged'] ?? 0);
            $paid = (float) ($row['paid'] ?? 0);
            $owe = $charged - $paid;
            $total += $owe;
            $base = "form1[0].sfrmForm[0].InfofromLandlord2[0].RentOwing[0].Row{$i}[0].";
            $values[$base . "DateFrom{$i}[0]"] = ! empty($row['from']) ? Carbon::parse($row['from'])->format('d/m/Y') : null;
            $values[$base . "DateTo{$i}[0]"] = ! empty($row['to']) ? Carbon::parse($row['to'])->format('d/m/Y') : null;
            $values[$base . "Charged{$i}[0]"] = number_format($charged, 2);
            $values[$base . "Paid{$i}[0]"] = number_format($paid, 2);
            $values[$base . "Owing{$i}[0]"] = number_format($owe, 2);
        }
        if ($total != 0.0) {
            $values['form1[0].sfrmForm[0].InfofromLandlord2[0].TotalRentOwing[0]'] = number_format($total, 2);
        }

        // Page 1 amount owing: the typed amount, otherwise the table total.
        $amount = $data['n4_amount_owing'] ?? null;
        if ($amount === null || $amount === '') {
            $amount = $total != 0.0 ? $total : null;
        }
        $values['form1[0].sfrmForm[0].InfofromLandlord1[0].txtOweMeAmount[0]'] = $amount !== null ? number_format((float) $amount, 2) : null;

        return $values;
    }

    // ------------------------------------------------------------------
    // N5 — Notice to End Tenancy Early (Interference / Damage)
    // ------------------------------------------------------------------

    protected function n5Schema(array $ctx): array
    {
        return [
            \Filament\Forms\Components\Section::make('Notice details')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('n5_tenant_name')->label("Tenant's name (include all tenant names)")
                        ->default($ctx['tenant_names'])->columnSpanFull(),
                    TextInput::make('n5_landlord_name')->label("Landlord's name")
                        ->default($ctx['landlord_name'])->columnSpanFull(),
                ]),

            \Filament\Forms\Components\Section::make('Address of the rental unit')
                ->columns(6)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('n5_street_number')->label('Street number')->columnSpan(2)
                        ->default($ctx['unit_street_number'] ?? ''),
                    TextInput::make('n5_street_name')->label('Street name')->columnSpan(4)
                        ->default($ctx['unit_street_name'] ?? ''),
                    TextInput::make('n5_unit')->label('Unit/Apt./Suite')->columnSpan(2)
                        ->default($ctx['unit_number'] ?? ''),
                    TextInput::make('n5_city')->label('Municipality (city, town, etc.)')->columnSpan(2)
                        ->default($ctx['unit_city'] ?? ''),
                    TextInput::make('n5_province')->label('Province')->columnSpan(1)
                        ->default($ctx['unit_province'] ?? 'ON'),
                    TextInput::make('n5_postal_code')->label('Postal code')->columnSpan(1)
                        ->default($ctx['unit_postal'] ?? ''),
                ]),

            DatePicker::make('n5_termination_date')->label('Termination date (move-out date)')
                ->native(false)->displayFormat('M d, Y')->default($ctx['today'])
                ->helperText('First N5: at least 20 days after service. Second N5 (within 6 months): at least 14 days. Add extra days for mail/courier.'),

            \Filament\Forms\Components\Section::make('Reason 1 — substantial interference')
                ->columns(1)->columnSpanFull()
                ->schema([
                    Checkbox::make('n5_reason1')->label('Include Reason 1 (checks the Reason 1 box on the form)')->live(),
                    Radio::make('n5_reason1_option')
                        ->label('Reason 1 — select the notice wording')
                        ->options([
                            'first' => 'You have 7 days to stop the activities or correct the behaviour described on page 2 and avoid eviction.',
                            'second' => 'I can apply to the LTB immediately for an order to evict you.',
                        ])
                        ->default('first')
                        ->visible(fn (Get $get): bool => (bool) $get('n5_reason1')),
                ]),

            \Filament\Forms\Components\Section::make('Reason 2 — wilful or negligent damage')
                ->columns(1)->columnSpanFull()
                ->schema([
                    Checkbox::make('n5_reason2')->label('Include Reason 2 (checks the Reason 2 box on the form)')->live(),
                    Radio::make('n5_reason2_option')
                        ->label('Reason 2 — select the notice wording')
                        ->options([
                            'first' => 'You have 7 days to correct the problem(s) described on page 2 and avoid eviction.',
                            'second' => 'I can apply to the LTB immediately for an order to evict you.',
                        ])
                        ->default('first')
                        ->visible(fn (Get $get): bool => (bool) $get('n5_reason2')),
                    TextInput::make('n5_pay_repair')->label('Cost to repair the damages ($)')->numeric()
                        ->visible(fn (Get $get): bool => (bool) $get('n5_reason2')),
                    TextInput::make('n5_pay_replace')->label('Cost to replace the damaged property ($)')->numeric()
                        ->visible(fn (Get $get): bool => (bool) $get('n5_reason2')),
                ]),

            \Filament\Forms\Components\Section::make('Reason 3 — overcrowding')
                ->columns(1)->columnSpanFull()
                ->schema([
                    Checkbox::make('n5_reason3')->label('Include Reason 3 (checks the Reason 3 box on the form)')->live(),
                    Radio::make('n5_reason3_option')
                        ->label('Reason 3 — select the notice wording')
                        ->options([
                            'first' => 'You have 7 days to reduce the number of people living in the rental unit to the number specified below.',
                            'second' => 'I can apply to the LTB immediately for an order to evict you.',
                        ])
                        ->default('first')
                        ->visible(fn (Get $get): bool => (bool) $get('n5_reason3')),
                    TextInput::make('n5_reason3_people')->label('Reduce number of people to (specify)')
                        ->visible(fn (Get $get): bool => (bool) $get('n5_reason3')),
                ]),

            Repeater::make('n5_events')->label('Details of the events (optional)')
                ->schema([
                    TextInput::make('event_datetime')->label('Date & time'),
                    Textarea::make('event_details')->label('Details')->rows(2),
                ])
                ->columns(2)->maxItems(3)->defaultItems(0)->columnSpanFull(),

            \Filament\Forms\Components\Section::make('Signature')
                ->columns(2)->columnSpanFull()
                ->schema([
                    Select::make('n5_signer')->label('Signing as')
                        ->options(['landlord' => 'Landlord', 'representative' => 'Representative'])
                        ->default(self::SIGNER_ROLE)->live()->columnSpanFull(),
                    TextInput::make('n5_signer_first_name')->label('First name')->default(self::SIGNER_FIRST),
                    TextInput::make('n5_signer_last_name')->label('Last name')->default(self::SIGNER_LAST),
                    TextInput::make('n5_signer_phone')->label('Phone number')->default(self::SIGNER_PHONE)->columnSpanFull(),
                    TextInput::make('n5_signature')->label('Signature (typed name)')->default(self::SIGNER_SIGNATURE),
                    DatePicker::make('n5_sign_date')->label('Date signed')->native(false)->displayFormat('M d, Y')->default($ctx['today']),
                ]),

            \Filament\Forms\Components\Section::make('Representative information')
                ->description('Completed only when the notice is signed by a representative.')
                ->columns(2)->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('n5_signer') === 'representative')
                ->schema([
                    TextInput::make('n5_rep_first_name')->label('First name')->default(self::SIGNER_FIRST),
                    TextInput::make('n5_rep_last_name')->label('Last name')->default(self::SIGNER_LAST),
                    TextInput::make('n5_rep_lso')->label('LSO number (if applicable)'),
                    TextInput::make('n5_rep_company')->label('Company name (if applicable)')
                        ->default($ctx['manager_company'] ?? self::MANAGER_COMPANY),
                    TextInput::make('n5_rep_street_number')->label('Street number')
                        ->default($ctx['manager_street_number'] ?? ''),
                    TextInput::make('n5_rep_street_name')->label('Street name')
                        ->default($ctx['manager_street_name'] ?? ''),
                    TextInput::make('n5_rep_unit')->label('Unit/Apt./Suite')
                        ->default($ctx['manager_unit'] ?? ''),
                    TextInput::make('n5_rep_city')->label('Municipality')
                        ->default($ctx['manager_city'] ?? ''),
                    TextInput::make('n5_rep_province')->label('Province')
                        ->default($ctx['manager_province'] ?? 'ON'),
                    TextInput::make('n5_rep_postal_code')->label('Postal code')
                        ->default($ctx['manager_postal'] ?? ''),
                    TextInput::make('n5_rep_email')->label('Email address')
                        ->default($ctx['manager_email'] ?? self::MANAGER_EMAIL),
                    TextInput::make('n5_rep_phone')->label('Phone number')->default(self::SIGNER_PHONE),
                ]),
        ];
    }

    protected function n5Values(array $data, array $ctx): array
    {
        $term = isset($data['n5_termination_date']) && $data['n5_termination_date'] ? Carbon::parse($data['n5_termination_date']) : null;
        $sign = Carbon::parse($data['n5_sign_date'] ?? $ctx['today']);
        $signer = $data['n5_signer'] ?? self::SIGNER_ROLE;
        $r1 = (bool) ($data['n5_reason1'] ?? false);
        $r2 = (bool) ($data['n5_reason2'] ?? false);
        $r3 = (bool) ($data['n5_reason3'] ?? false);
        $r1opt = $data['n5_reason1_option'] ?? '7day';
        $r2opt = $data['n5_reason2_option'] ?? '7day';
        $r3opt = $data['n5_reason3_option'] ?? '7day';

        // The on-disk template is the redesigned LTB N5 (2026/09). Its widgets are
        // nested and use different names from the old N5, so map by the full widget
        // path — the short-name fallback in fill_form.py can collide.
        $b = 'form1[0].sfrmForm[0].';
        $values = [
            // Page 1 — parties + structured unit address + termination date
            $b . 'PartyAddressInfo[0].txtTenant[0]' => $data['n5_tenant_name'] ?? null,
            $b . 'PartyAddressInfo[0].txtLandlord[0]' => $data['n5_landlord_name'] ?? null,
            $b . 'PartyAddressInfo[0].txtNumber[0]' => $data['n5_street_number'] ?? null,
            $b . 'PartyAddressInfo[0].txtName[0]' => $data['n5_street_name'] ?? null,
            $b . 'PartyAddressInfo[0].txtUnit[0]' => $data['n5_unit'] ?? null,
            $b . 'PartyAddressInfo[0].txtCity[0]' => $data['n5_city'] ?? null,
            $b . 'PartyAddressInfo[0].txtProvince[0]' => $data['n5_province'] ?: 'ON',
            $b . 'PartyAddressInfo[0].txtPostalCode[0]' => $data['n5_postal_code'] ?? null,
            $b . 'InfofromLandlord1[0].TerminationDate[0]' => $term?->format('d/m/Y'),

            // Reason 1 — substantial interference (radio #0 = first notice, #1 = second)
            $b . 'Reasons[0].Reason1[0].Reason1[0]' => $r1 ? 'X' : null,
            $b . 'Reasons[0].Reason1[0].Reason1_1[0]#0' => $r1 && $r1opt === 'first' ? 'X' : null,
            $b . 'Reasons[0].Reason1[0].Reason1_1[0]#1' => $r1 && $r1opt === 'second' ? 'X' : null,

            // Reason 2 — wilful/negligent damage (7-day radio on p1, immediate on p2)
            $b . 'Reasons[0].Reason2[0].Reason2[0]' => $r2 ? 'X' : null,
            $b . 'Reasons[0].Reason2[0].Reason2_1[0]' => $r2 && $r2opt === 'first' ? 'X' : null,
            $b . 'Reasons[0].Reason2[0].Reason2_2[0]' => $r2 && $r2opt === 'second' ? 'X' : null,
            $b . 'Reasons[0].Reason2[0].PayMe1[0]' => $data['n5_pay_repair'] ?? null,
            $b . 'Reasons[0].Reason2[0].PayMe2[0]' => $data['n5_pay_replace'] ?? null,

            // Reason 3 — overcrowding
            $b . 'Reasons[0].Reason3[0].Reason3[0]' => $r3 ? 'X' : null,
            $b . 'Reasons[0].Reason3[0].Reason3_1[0]' => $r3 && $r3opt === 'first' ? 'X' : null,
            $b . 'Reasons[0].Reason3[0].Reason3_2[0]' => $r3 && $r3opt === 'second' ? 'X' : null,
            $b . 'Reasons[0].Reason3[0].Reason3Explain[0]' => $data['n5_reason3_people'] ?? null,

            // Signature block
            $b . 'Signature[0].PartyType[0]#0' => $signer === 'landlord' ? 'X' : null,
            $b . 'Signature[0].PartyType[0]#1' => $signer === 'representative' ? 'X' : null,
            $b . 'Signature[0].txtFirstName[0]' => $data['n5_signer_first_name'] ?? null,
            $b . 'Signature[0].txtLastName[0]' => $data['n5_signer_last_name'] ?? null,
            $b . 'Signature[0].txtPhone1[0]' => $data['n5_signer_phone'] ?? null,
            $b . 'Signature[0].Signature[0]' => $data['n5_signature'] ?? null,
            $b . 'Signature[0].dptSignDate[0]' => $sign->format('d/m/Y'),
        ];

        if ($signer === 'representative') {
            $rep = $b . 'LegalRepInfo[0].sfrmLegalRepInfo_1[0].';
            $values[$rep . 'txtFirstName[0]'] = $data['n5_rep_first_name'] ?? null;
            $values[$rep . 'txtLastName[0]'] = $data['n5_rep_last_name'] ?? null;
            $values[$rep . 'txtLSOnumber[0]'] = $data['n5_rep_lso'] ?? null;
            $values[$rep . 'txtCompanyName[0]'] = $data['n5_rep_company'] ?? null;
            $values[$rep . 'txtNumber[0]'] = $data['n5_rep_street_number'] ?? null;
            $values[$rep . 'txtName[0]'] = $data['n5_rep_street_name'] ?? null;
            $values[$rep . 'txtUnit[0]'] = $data['n5_rep_unit'] ?? null;
            $values[$rep . 'txtCity[0]'] = $data['n5_rep_city'] ?? null;
            $values[$rep . 'txtProvince[0]'] = $data['n5_rep_province'] ?: 'ON';
            $values[$rep . 'txtPostalCode[0]'] = $data['n5_rep_postal_code'] ?? null;
            $values[$rep . 'txtEmail[0]'] = $data['n5_rep_email'] ?? null;
            $values[$rep . 'txtPhone1[0]'] = $data['n5_rep_phone'] ?? null;
        }

        $events = $data['n5_events'] ?? [];
        $tbl = $b . 'Details[0].Table1[0].';
        foreach (range(1, 3) as $i) {
            $row = $events[$i - 1] ?? null;
            if (! $row) {
                continue;
            }
            $values[$tbl . "Row{$i}[0].EventDateTime{$i}[0]"] = $row['event_datetime'] ?? null;
            $values[$tbl . "Row{$i}[0].Event{$i}[0]"] = $row['event_details'] ?? null;
        }

        return $values;
    }

    // ------------------------------------------------------------------
    // N11 — Agreement to End the Tenancy
    // ------------------------------------------------------------------

    protected function n11Schema(array $ctx): array
    {
        return [
            TextInput::make('n11_tenant_names')->label('Tenant name(s) — include all tenants')->default($ctx['tenant_names']),
            TextInput::make('n11_landlord_name')->label('Landlord name')->default($ctx['landlord_name']),
            TextInput::make('n11_unit_address')->label('Address of the Rental Unit')->default($ctx['unit_address'])->columnSpanFull(),
            DatePicker::make('n11_termination_date')->label('Date the tenancy ends')
                ->native(false)->displayFormat('M d, Y'),
            TextInput::make('n11_tenant_first_name')->label('Tenant signer — first name')->default($ctx['tenant_first']),
            TextInput::make('n11_tenant_last_name')->label('Tenant signer — last name')->default($ctx['tenant_last']),
            TextInput::make('n11_tenant_phone')->label('Tenant phone')->default($ctx['tenant_phone']),
            TextInput::make('n11_landlord_first_name')->label('Landlord signer — first name')->default(self::SIGNER_FIRST),
            TextInput::make('n11_landlord_last_name')->label('Landlord signer — last name')->default(self::SIGNER_LAST),
            TextInput::make('n11_landlord_phone')->label('Landlord signer phone')->default(self::SIGNER_PHONE),
            DatePicker::make('n11_sign_date')->label('Date signed')->native(false)->displayFormat('M d, Y')->default($ctx['today']),
        ];
    }

    protected function n11Values(array $data, array $ctx): array
    {
        $term = isset($data['n11_termination_date']) && $data['n11_termination_date'] ? Carbon::parse($data['n11_termination_date']) : null;
        $sign = Carbon::parse($data['n11_sign_date'] ?? $ctx['today']);
        [$tArea, $tPre, $tLine] = $this->splitPhone($data['n11_tenant_phone'] ?? $ctx['tenant_phone']);
        [$lArea, $lPre, $lLine] = $this->splitPhone($data['n11_landlord_phone'] ?? $ctx['landlord_phone']);

        return [
            'Name of Tenant include all tenant namesRow1' => $data['n11_tenant_names'] ?? null,
            'Name of LandlordRow1' => $data['n11_landlord_name'] ?? null,
            'Address of the Rental UnitRow1' => $data['n11_unit_address'] ?? null,
            'DD' => $term?->format('d'),
            'MM' => $term?->format('m'),
            'YYYY' => $term?->format('Y'),
            'First Name' => $data['n11_tenant_first_name'] ?? null,
            'Last Name' => $data['n11_tenant_last_name'] ?? null,
            'undefined_2' => $tArea,
            'undefined_3' => $tPre,
            'undefined_4' => $tLine,
            'First Name_2' => $data['n11_landlord_first_name'] ?? null,
            'Last Name_2' => $data['n11_landlord_last_name'] ?? null,
            'undefined_5' => $lArea,
            'undefined_6' => $lPre,
            'undefined_7' => $lLine,
            'Date ddmmyyyy_2' => $sign->format('d/m/Y'),
        ];
    }

    // ------------------------------------------------------------------
    // N9 — Tenant's Notice to End the Tenancy
    // ------------------------------------------------------------------

    protected function n9Schema(array $ctx): array
    {
        return [
            TextInput::make('n9_tenant_names')->label('Tenant name(s) — include all tenants')->default($ctx['tenant_names']),
            TextInput::make('n9_landlord_name')->label('Landlord name')->default($ctx['landlord_name']),
            TextInput::make('n9_unit_address')->label('Address of the Rental Unit')->default($ctx['unit_address'])->columnSpanFull(),
            DatePicker::make('n9_termination_date')->label('Date the tenancy ends')
                ->native(false)->displayFormat('M d, Y'),
            TextInput::make('n9_tenant_first_name')->label('Tenant — first name')->default($ctx['tenant_first']),
            TextInput::make('n9_tenant_last_name')->label('Tenant — last name')->default($ctx['tenant_last']),
            TextInput::make('n9_tenant_phone')->label('Tenant phone')->default($ctx['tenant_phone']),
            Select::make('n9_signer')->label('Signing as')->options(['tenant' => 'Tenant', 'representative' => 'Representative'])->default('tenant'),
            DatePicker::make('n9_sign_date')->label('Date signed')->native(false)->displayFormat('M d, Y')->default($ctx['today']),
        ];
    }

    protected function n9Values(array $data, array $ctx): array
    {
        $term = isset($data['n9_termination_date']) && $data['n9_termination_date'] ? Carbon::parse($data['n9_termination_date']) : null;
        $sign = Carbon::parse($data['n9_sign_date'] ?? $ctx['today']);
        $signer = $data['n9_signer'] ?? 'tenant';
        [$area, $pre, $line] = $this->splitPhone($data['n9_tenant_phone'] ?? $ctx['tenant_phone']);

        return [
            'To Landlords nameRow1' => $data['n9_landlord_name'] ?? null,
            'From Tenants name include all tenant namesRow1' => $data['n9_tenant_names'] ?? null,
            'Address of the Rental UnitRow1' => $data['n9_unit_address'] ?? null,
            'DD' => $term?->format('d'),
            'MM' => $term?->format('m'),
            'YYYY' => $term?->format('Y'),
            'First Name' => $data['n9_tenant_first_name'] ?? null,
            'Last Name' => $data['n9_tenant_last_name'] ?? null,
            'undefined_2' => $area,
            'undefined_3' => $pre,
            'undefined_4' => $line,
            'Tenant' => $signer === 'tenant' ? 'X' : null,
            'Representative' => $signer === 'representative' ? 'X' : null,
            'Date ddmmyyyy' => $sign->format('d/m/Y'),
        ];
    }

    // ------------------------------------------------------------------

    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name));
        if (empty($parts) || $parts[0] === '') {
            return ['', ''];
        }
        $first = array_shift($parts);

        return [$first, implode(' ', $parts)];
    }

    private function splitPhone(?string $phone): array
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return [
            substr($digits, -10, 3) ?: '',
            substr($digits, -7, 3) ?: '',
            substr($digits, -4) ?: '',
        ];
    }
}
