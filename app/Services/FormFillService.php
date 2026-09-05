<?php

namespace App\Services;

use App\Models\Form;
use App\Models\Tenancy;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
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
                'n4_unit_address' => $ctx['unit_address'],
                'n4_pay_date' => $today,
                'n4_signer' => self::SIGNER_ROLE,
                'n4_signer_first_name' => self::SIGNER_FIRST,
                'n4_signer_last_name' => self::SIGNER_LAST,
                'n4_signer_phone' => self::SIGNER_PHONE,
                'n4_signature' => self::SIGNER_SIGNATURE,
                'n4_sign_date' => $today,
            ],
            'n5' => [
                'n5_tenant_name' => $ctx['tenant_names'],
                'n5_landlord_name' => $ctx['landlord_name'],
                'n5_unit_address' => $ctx['unit_address'],
                'n5_termination_date' => $today,
                'n5_reason1_option' => '7day',
                'n5_reason2_option' => '7day',
                'n5_reason3_option' => '7day',
                'n5_signer' => self::SIGNER_ROLE,
                'n5_signer_first_name' => self::SIGNER_FIRST,
                'n5_signer_last_name' => self::SIGNER_LAST,
                'n5_signer_phone' => self::SIGNER_PHONE,
                'n5_signature' => self::SIGNER_SIGNATURE,
                'n5_sign_date' => $today,
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
            'n5' => ['Signature' => ['helv', 12], 'SignDate' => ['helv', 12]],
            'n4' => ['Signature' => ['helv', 12], 'SignDate' => ['helv', 12]],
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
            TextInput::make('n4_tenant_name')->label("Tenant's Name")->default($ctx['tenant_names']),
            TextInput::make('n4_landlord_name')->label("Landlord's Name")->default($ctx['landlord_name']),
            TextInput::make('n4_unit_address')->label('Address of the Rental Unit')
                ->default($ctx['unit_address'])->columnSpanFull(),
            TextInput::make('n4_amount_owing')->label('Amount of rent owing ($)')->numeric(),
            DatePicker::make('n4_pay_date')->label('Pay by / termination date')
                ->native(false)->displayFormat('M d, Y')->default($ctx['today']),
            Repeater::make('n4_arrears')->label('Rent arrears breakdown (optional)')
                ->schema([
                    DatePicker::make('from')->label('Period from')->native(false)->displayFormat('M d, Y'),
                    DatePicker::make('to')->label('Period to')->native(false)->displayFormat('M d, Y'),
                    TextInput::make('charged')->label('Rent charged ($)')->numeric(),
                    TextInput::make('paid')->label('Rent paid ($)')->numeric(),
                ])
                ->columns(4)->maxItems(3)->defaultItems(0)->columnSpanFull()
                ->helperText('Owing is calculated per row (charged − paid); the total is filled in automatically.'),
            Select::make('n4_signer')->label('Signing as')->options(['landlord' => 'Landlord', 'representative' => 'Representative'])->default(self::SIGNER_ROLE),
            TextInput::make('n4_signer_first_name')->label('Signer first name')->default(self::SIGNER_FIRST),
            TextInput::make('n4_signer_last_name')->label('Signer last name')->default(self::SIGNER_LAST),
            TextInput::make('n4_signer_phone')->label('Signer phone')->default(self::SIGNER_PHONE),
            TextInput::make('n4_signature')->label('Signature (typed name)')->default(self::SIGNER_SIGNATURE),
            DatePicker::make('n4_sign_date')->label('Date signed')->native(false)->displayFormat('M d, Y')->default($ctx['today']),
        ];
    }

    protected function n4Values(array $data, array $ctx): array
    {
        $sign = Carbon::parse($data['n4_sign_date'] ?? $ctx['today']);
        $pay = isset($data['n4_pay_date']) && $data['n4_pay_date'] ? Carbon::parse($data['n4_pay_date']) : null;
        $signer = $data['n4_signer'] ?? self::SIGNER_ROLE;

        $values = [
            'TO_TenameName' => $data['n4_tenant_name'] ?? null,
            'From_LandlordName' => $data['n4_landlord_name'] ?? null,
            'RentalUnitAddress' => $data['n4_unit_address'] ?? null,
            'OweMeAmount' => $data['n4_amount_owing'] ?? null,
            'PayDate' => $pay?->format('d/m/Y'),
            'SelectSign#0' => $signer === 'landlord' ? 'X' : null,
            'SelectSign#1' => $signer === 'representative' ? 'X' : null,
            'RFirstName' => $data['n4_signer_first_name'] ?? null,
            'RLastName' => $data['n4_signer_last_name'] ?? null,
            'RDayPhone' => $data['n4_signer_phone'] ?? null,
            'Signature' => $data['n4_signature'] ?? null,
            'SignDate' => $sign->format('d/m/Y'),
        ];

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
            $values["ArrearFrom{$i}"] = isset($row['from']) && $row['from'] ? Carbon::parse($row['from'])->format('d/m/Y') : null;
            $values["ArrearTo{$i}"] = isset($row['to']) && $row['to'] ? Carbon::parse($row['to'])->format('d/m/Y') : null;
            $values["RentCharge{$i}"] = number_format($charged, 2);
            $values["RentPaid{$i}"] = number_format($paid, 2);
            $values["RentOwe{$i}"] = number_format($owe, 2);
        }
        if ($total != 0.0) {
            $values['TotalRentOwe'] = number_format($total, 2);
        }

        return $values;
    }

    // ------------------------------------------------------------------
    // N5 — Notice to End Tenancy Early (Interference / Damage)
    // ------------------------------------------------------------------

    protected function n5Schema(array $ctx): array
    {
        return [
            TextInput::make('n5_tenant_name')->label("Tenant's Name")->default($ctx['tenant_names']),
            TextInput::make('n5_landlord_name')->label("Landlord's Name")->default($ctx['landlord_name']),
            TextInput::make('n5_unit_address')->label('Address of the Rental Unit')
                ->default($ctx['unit_address'])->columnSpanFull(),
            DatePicker::make('n5_termination_date')->label('Termination date (move-out date)')
                ->native(false)->displayFormat('M d, Y')->default($ctx['today']),
            Toggle::make('n5_reason1')->label('Reason 1 — substantial interference with reasonable enjoyment')->live(),
            Select::make('n5_reason1_option')->label('Reason 1 option')
                ->options(['7day' => '7 days to correct (first notice)', 'immediate' => 'Apply immediately (2nd notice in 6 months)'])
                ->default('7day')->visible(fn (Get $get): bool => (bool) $get('n5_reason1')),
            Toggle::make('n5_reason2')->label('Reason 2 — wilful or negligent damage')->live(),
            Select::make('n5_reason2_option')->label('Reason 2 option')
                ->options(['7day' => '7 days to correct (first notice)', 'immediate' => 'Apply immediately (2nd notice in 6 months)'])
                ->default('7day')->visible(fn (Get $get): bool => (bool) $get('n5_reason2')),
            TextInput::make('n5_pay_repair')->label('Cost to repair the damages ($)')->numeric()->columnSpan(1),
            TextInput::make('n5_pay_replace')->label('Cost to replace the damaged property ($)')->numeric()->columnSpan(1),
            Toggle::make('n5_reason3')->label('Reason 3 — more people than permitted (overcrowding)')->live(),
            Select::make('n5_reason3_option')->label('Reason 3 option')
                ->options(['7day' => '7 days to correct (first notice)', 'immediate' => 'Apply immediately (2nd notice in 6 months)'])
                ->default('7day')->visible(fn (Get $get): bool => (bool) $get('n5_reason3')),
            TextInput::make('n5_reason3_people')->label('Reduce number of people to (specify)')
                ->visible(fn (Get $get): bool => (bool) $get('n5_reason3')),
            Repeater::make('n5_events')->label('Details of the events (optional)')
                ->schema([
                    TextInput::make('event_datetime')->label('Date & time'),
                    Textarea::make('event_details')->label('Details')->rows(2),
                ])
                ->columns(2)->maxItems(3)->defaultItems(0)->columnSpanFull(),
            Select::make('n5_signer')->label('Signing as')->options(['landlord' => 'Landlord', 'representative' => 'Representative'])->default(self::SIGNER_ROLE),
            TextInput::make('n5_signer_first_name')->label('Signer first name')->default(self::SIGNER_FIRST),
            TextInput::make('n5_signer_last_name')->label('Signer last name')->default(self::SIGNER_LAST),
            TextInput::make('n5_signer_phone')->label('Signer phone')->default(self::SIGNER_PHONE),
            TextInput::make('n5_signature')->label('Signature (typed name)')->default(self::SIGNER_SIGNATURE),
            DatePicker::make('n5_sign_date')->label('Date signed')->native(false)->displayFormat('M d, Y')->default($ctx['today']),
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

        $values = [
            'TO_TenameName' => $data['n5_tenant_name'] ?? null,
            'From_LandlordName' => $data['n5_landlord_name'] ?? null,
            'RentalUnitAddress' => $data['n5_unit_address'] ?? null,
            'TerminationDate' => $term?->format('d/m/Y'),
            'Reason1' => $r1 ? 'X' : null,
            'Reason1_1#0' => $r1 && $r1opt === '7day' ? 'X' : null,
            'Reason1_1#1' => $r1 && $r1opt === 'immediate' ? 'X' : null,
            'Reason2' => $r2 ? 'X' : null,
            'Reason2_1' => $r2 && $r2opt === '7day' ? 'X' : null,
            'Reason2_2' => $r2 && $r2opt === 'immediate' ? 'X' : null,
            'Reason3' => $r3 ? 'X' : null,
            'Reason3_1' => $r3 && $r3opt === '7day' ? 'X' : null,
            'Reason3_2' => $r3 && $r3opt === 'immediate' ? 'X' : null,
            'Reason3Explain' => $data['n5_reason3_people'] ?? null,
            'PayMe1' => $data['n5_pay_repair'] ?? null,
            'PayMe2' => $data['n5_pay_replace'] ?? null,
            'SelectSign#0' => $signer === 'landlord' ? 'X' : null,
            'SelectSign#1' => $signer === 'representative' ? 'X' : null,
            'RFirstName' => $data['n5_signer_first_name'] ?? null,
            'RLastName' => $data['n5_signer_last_name'] ?? null,
            'RDayPhone' => $data['n5_signer_phone'] ?? null,
            'Signature' => $data['n5_signature'] ?? null,
            'SignDate' => $sign->format('d/m/Y'),
        ];

        $events = $data['n5_events'] ?? [];
        foreach (range(1, 3) as $i) {
            $row = $events[$i - 1] ?? null;
            if (! $row) {
                continue;
            }
            $values["EventDateTime{$i}"] = $row['event_datetime'] ?? null;
            $values["Event{$i}"] = $row['event_details'] ?? null;
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
