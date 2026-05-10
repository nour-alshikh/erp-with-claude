<?php

namespace App\Jobs\HR;

use App\Modules\HR\Models\Employee;
use App\Modules\HR\Models\PayrollRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GeneratePayslipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $payrollRunId,
        private readonly int $employeeId,
    ) {}

    public function handle(): void
    {
        $run = PayrollRun::with([
            'payrollItems' => fn ($q) => $q->where('employee_id', $this->employeeId),
        ])->findOrFail($this->payrollRunId);

        $employee = Employee::findOrFail($this->employeeId);

        $items      = $run->payrollItems;
        $earnings   = $items->where('type', 'earning')->sum('amount');
        $deductions = $items->where('type', 'deduction')->sum('amount');
        $netPay     = $earnings - $deductions;

        $html = $this->buildHtml($run, $employee, $items, $earnings, $deductions, $netPay);

        Storage::put(
            "payslips/run-{$this->payrollRunId}/emp-{$this->employeeId}.html",
            $html,
        );
    }

    private function buildHtml(
        PayrollRun $run,
        Employee $employee,
        $items,
        int $earnings,
        int $deductions,
        int $netPay,
    ): string {
        $month   = date('F', mktime(0, 0, 0, $run->month, 1));
        $fmt     = fn (int $cents) => number_format($cents / 100, 2);
        $rows    = '';

        foreach ($items as $item) {
            $sign  = $item->type === 'deduction' ? '-' : '';
            $rows .= "<tr><td>{$item->description}</td><td>{$sign}{$fmt($item->amount)}</td></tr>";
        }

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Payslip — {$month} {$run->year}</title>
          <style>body{font-family:sans-serif;padding:2rem} table{width:100%;border-collapse:collapse} td,th{border:1px solid #ddd;padding:.5rem}</style>
        </head>
        <body>
          <h2>Payslip — {$month} {$run->year}</h2>
          <p><strong>Employee:</strong> {$employee->name}</p>
          <table>
            <thead><tr><th>Description</th><th>Amount</th></tr></thead>
            <tbody>{$rows}</tbody>
          </table>
          <p><strong>Total Earnings:</strong> {$fmt($earnings)}</p>
          <p><strong>Total Deductions:</strong> {$fmt($deductions)}</p>
          <p><strong>Net Pay:</strong> {$fmt($netPay)}</p>
        </body>
        </html>
        HTML;
    }
}
