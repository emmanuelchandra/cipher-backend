<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    public function generate(User $employee, int $month, int $year): Report
    {
        $attendances = Attendance::where('user_id', $employee->id)
            ->whereMonth('check_in', $month)
            ->whereYear('check_in', $year)
            ->with('user.department')
            ->get();

        $summary = $this->buildSummary($attendances, $month, $year);

        $pdfPath = $this->generatePdf($employee, $summary, $month, $year);

        return Report::updateOrCreate(
            ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
            ['pdf_path' => $pdfPath, 'status' => 'pending', 'signed_pdf_path' => null]
        );
    }

    public function approve(Report $report, User $approver): Report
    {
        $signedPath = $this->applySignature($report, $approver);

        $report->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'signed_pdf_path' => $signedPath,
        ]);

        return $report->fresh();
    }

    public function buildSummary(iterable $attendances, int $month, int $year): array
    {
        $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $records = collect($attendances);

        return [
            'total_present' => $records->count(),
            'total_absent' => $totalDays - $records->count(),
            'total_late' => $records->where('status', 'late')->count(),
            'total_on_time' => $records->where('status', 'on_time')->count(),
            'total_overtime_minutes' => $records->sum('overtime_minutes'),
            'attendances' => $records->toArray(),
        ];
    }

    private function generatePdf(User $employee, array $summary, int $month, int $year): string
    {
        $settings = CompanySetting::first();
        $monthName = Carbon::createFromDate($year, $month, 1)->format('F Y');

        $html = view('reports.attendance', compact('employee', 'summary', 'monthName', 'settings'))->render();

        // Using a simple HTML-to-text approach for now; integrate with DomPDF or Browsershot for real PDF
        $filename = "reports/{$employee->id}_{$month}_{$year}.html";
        Storage::disk('r2')->put($filename, $html, 'public');

        return $filename;
    }

    private function applySignature(Report $report, User $approver): string
    {
        // In production: overlay approver's signature on the PDF
        // For now, copy the PDF to a signed path
        $signedPath = str_replace('.html', '_signed.html', $report->pdf_path);
        if (Storage::disk('r2')->exists($report->pdf_path)) {
            Storage::disk('r2')->copy($report->pdf_path, $signedPath);
        }
        return $signedPath;
    }
}
