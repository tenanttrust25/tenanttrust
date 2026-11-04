<?php

namespace App\Services;

use App\Models\Form;
use App\Models\N9Submission;
use RuntimeException;
use Symfony\Component\Process\Process;

class N9PdfService
{
    /**
     * Generate a filled N9 PDF for a submission using the N9 template
     * uploaded in the admin Forms library.
     *
     * @return string relative public-storage path, e.g. "n9-submissions/N9_1_20260901.pdf"
     */
    public function generate(N9Submission $submission): string
    {
        $template = $this->templatePath();

        $outputDir = storage_path('app/documents/n9-submissions');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $filename = 'N9_' . $submission->id . '_' . $submission->termination_date->format('Ymd') . '.pdf';
        $output = $outputDir . '/' . $filename;

        $process = new Process([
            'python3',
            base_path('scripts/fill_n9.py'),
            $template,
            $output,
            json_encode($this->buildValues($submission)),
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('N9 PDF generation failed: ' . $process->getErrorOutput());
        }

        return 'n9-submissions/' . $filename;
    }

    protected function templatePath(): string
    {
        // Preferred: a private copy that survives form deletion from the admin library.
        $private = storage_path('app/private/n9-template.pdf');
        if (file_exists($private)) {
            return $private;
        }

        // Fallback: the N9 template uploaded in the admin Forms library.
        $form = Form::where('name', 'like', '%N9%')->first();
        if (! $form) {
            throw new RuntimeException('N9 template form not found in Forms library.');
        }

        return storage_path('app/public/' . $form->file_path);
    }

    protected function buildValues(N9Submission $s): array
    {
        $date = $s->termination_date;
        [$first, $last] = $this->splitName($s->signature ?: $s->tenant_names);
        [$area, $prefix, $line] = $this->splitPhone($s->phone);

        return [
            "To Landlords nameRow1" => $s->landlord_name ?? '',
            "From Tenants name include all tenant namesRow1" => $s->tenant_names ?? '',
            "Address of the Rental UnitRow1" => $s->unit_address ?? '',
            "DD" => $date->format('d'),
            "MM" => $date->format('m'),
            "YYYY" => $date->format('Y'),
            "Tenant" => "/On",
            "First Name" => $first,
            "Last Name" => $last,
            "undefined_2" => $area,
            "undefined_3" => $prefix,
            "undefined_4" => $line,
            "Signature" => $s->signature ?? '',
            "Date ddmmyyyy" => now()->format('d/m/Y'),
        ];
    }

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
