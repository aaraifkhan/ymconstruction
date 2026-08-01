<?php

namespace App\Actions\Payroll;

use App\Models\PayrollEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class GenerateSalarySlipAction
{
    public function handle(PayrollEntry $entry): string
    {
        $entry->loadMissing([
            'company',
            'payrollRun',
            'employment.employee',
            'employment.department',
            'employment.designation',
            'components',
        ]);

        $pdf = Pdf::loadView('pdf.salary-slip', [
            'entry' => $entry,
            'company' => $entry->company,
            'employment' => $entry->employment,
            'employee' => $entry->employment->employee,
            'run' => $entry->payrollRun,
        ])->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    public function download(PayrollEntry $entry): Response
    {
        $content = $this->handle($entry);
        $filename = "SalarySlip_{$entry->employee_code}_{$entry->payrollRun->period_end->format('Y_m')}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
