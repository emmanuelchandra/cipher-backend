<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function pending(): JsonResponse
    {
        $reports = Report::with(['employee.department', 'approver'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json(ReportResource::collection($reports));
    }

    public function generate(int $employeeId, int $month, int $year): JsonResponse
    {
        $employee = User::findOrFail($employeeId);
        $report = $this->reportService->generate($employee, $month, $year);

        return response()->json(new ReportResource($report->load(['employee', 'approver'])), 201);
    }

    public function approve(int $id): JsonResponse
    {
        $report = Report::findOrFail($id);

        if ($report->status !== 'pending') {
            return response()->json(['message' => 'Report is not in pending status.'], 422);
        }

        $report = $this->reportService->approve($report, request()->user());

        return response()->json(new ReportResource($report->load(['employee', 'approver'])));
    }

    public function download(int $id): Response|JsonResponse
    {
        $report = Report::findOrFail($id);

        $path = $report->signed_pdf_path ?? $report->pdf_path;

        if (!$path || !Storage::disk('r2')->exists($path)) {
            return response()->json(['message' => 'Report file not found.'], 404);
        }

        $content = Storage::disk('r2')->get($path);
        $filename = basename($path);

        return response($content, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
