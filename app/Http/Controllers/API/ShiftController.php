<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;

class ShiftController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(ShiftResource::collection(Shift::all()));
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = Shift::create($request->validated());

        return response()->json(new ShiftResource($shift), 201);
    }

    public function update(UpdateShiftRequest $request, int $id): JsonResponse
    {
        $shift = Shift::findOrFail($id);
        $shift->update($request->validated());

        return response()->json(new ShiftResource($shift));
    }

    public function destroy(int $id): JsonResponse
    {
        Shift::findOrFail($id)->delete();

        return response()->json(['message' => 'Shift deleted.']);
    }
}
