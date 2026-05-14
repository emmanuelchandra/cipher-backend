<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\AssignShiftRequest;
use App\Http\Requests\Employee\UploadSignatureRequest;
use App\Http\Resources\UserResource;
use App\Models\EmployeeShift;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(): JsonResponse
    {
        $employees = User::with('department')
            ->where('role', 'employee')
            ->get();

        return response()->json(UserResource::collection($employees));
    }

    public function assignShift(AssignShiftRequest $request): JsonResponse
    {
        $shift = EmployeeShift::create($request->validated());

        return response()->json([
            'message' => 'Shift assigned successfully.',
            'data' => $shift->load(['user', 'shift']),
        ], 201);
    }

    public function uploadSignature(UploadSignatureRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->signature_path) {
            Storage::disk('r2')->delete($user->signature_path);
        }

        $path = $request->file('signature')->store("signatures/{$user->id}", 'r2');

        $user->update(['signature_path' => $path]);

        return response()->json([
            'message' => 'Signature uploaded successfully.',
            'signature_url' => Storage::disk('r2')->url($path),
        ]);
    }
}
