<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\FaceCheckRequest;
use App\Http\Requests\Face\RegisterFaceRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\FaceDescriptorResource;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\FaceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class FaceController extends Controller
{
    public function __construct(
        private FaceService $faceService,
        private AttendanceService $attendanceService,
    ) {}

    public function register(RegisterFaceRequest $request): JsonResponse
    {
        $user = $request->filled('user_id')
            ? User::findOrFail($request->input('user_id'))
            : $request->user();

        // descriptor is already normalised to [[...], [...], ...] by prepareForValidation
        $record = null;
        foreach ($request->input('descriptor') as $sample) {
            $record = $this->faceService->register($user, $sample);
        }

        return response()->json([
            'message'        => 'Face registered successfully.',
            'samples_stored' => count($record?->descriptor ?? []),
            'data'           => new FaceDescriptorResource($record),
        ], 201);
    }

    public function checkIn(FaceCheckRequest $request): JsonResponse
    {
        $user = $this->faceService->findMatchingUser(array_values((array) $request->input('descriptor')));

        if (!$user) {
            return response()->json(['message' => 'Face not recognized.'], 404);
        }

        $existing = Attendance::where('user_id', $user->getKey())
            ->whereDate('check_in', today())
            ->whereNull('check_out')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already checked in today.'], 409);
        }

        $now = Carbon::now();

        if ($this->attendanceService->isAnomaly($user, $now)) {
            return response()->json([
                'message' => 'Anomaly detected: check-in time is more than 2 hours from shift start.',
                'user' => $user->only('id', 'name'),
            ], 422);
        }

        $attendance = $this->attendanceService->checkIn($user, $now);

        return response()->json([
            'message' => 'Check-in successful.',
            'data' => new AttendanceResource($attendance->load('user')),
        ], 201);
    }

    public function checkOut(FaceCheckRequest $request): JsonResponse
    {
        $user = $this->faceService->findMatchingUser(array_values((array) $request->input('descriptor')));

        if (!$user) {
            return response()->json(['message' => 'Face not recognized.'], 404);
        }

        $attendance = Attendance::where('user_id', $user->getKey())
            ->whereDate('check_in', today())
            ->whereNull('check_out')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'No active check-in found for today.'], 404);
        }

        $attendance = $this->attendanceService->checkOut($attendance, Carbon::now());

        return response()->json([
            'message' => 'Check-out successful.',
            'data' => new AttendanceResource($attendance->load('user')),
        ]);
    }

    public function descriptor(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $descriptor = $user->faceDescriptor;

        if (!$descriptor) {
            return response()->json(['message' => 'No face descriptor registered.'], 404);
        }

        return response()->json(new FaceDescriptorResource($descriptor));
    }
}
