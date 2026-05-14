<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function dashboard(): JsonResponse
    {
        return response()->json($this->attendanceService->getDashboard());
    }

    /**
     * Today's attendance for the authenticated user.
     * GET /api/attendance/today
     */
    public function today(Request $request): JsonResponse
    {
        $attendances = Attendance::with('user')
            ->where('user_id', $request->user()->getKey())
            ->whereDate('check_in', today())
            ->latest('check_in')
            ->get();

        return response()->json([
            'data' => AttendanceResource::collection($attendances),
            'date' => today()->toDateString(),
        ]);
    }

    /**
     * Paginated attendance history for any user.
     * Supports optional ?date=YYYY-MM-DD filter.
     * GET /api/attendance/history/{userId}
     */
    public function history(Request $request, int $userId): JsonResponse
    {
        User::findOrFail($userId);

        $query = Attendance::with('user')
            ->where('user_id', $userId)
            ->latest('check_in');

        if ($request->filled('date')) {
            $query->whereDate('check_in', $request->input('date'));
        }

        $attendances = $query->paginate(20);

        return response()->json([
            'data' => AttendanceResource::collection($attendances->items()),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page'    => $attendances->lastPage(),
                'total'        => $attendances->total(),
                'per_page'     => $attendances->perPage(),
            ],
        ]);
    }

    public function lateToday(): JsonResponse
    {
        $late = $this->attendanceService->getLateToday();

        return response()->json(AttendanceResource::collection($late));
    }

    public function anomalies(): JsonResponse
    {
        $anomalies = $this->attendanceService->getAnomalies();

        return response()->json(AttendanceResource::collection(
            $anomalies->map(fn ($user) => $user->attendances->first())
                ->filter()
                ->load('user')
        ));
    }
}
