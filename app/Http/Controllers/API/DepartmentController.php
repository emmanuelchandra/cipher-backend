<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    public function index(): JsonResponse
    {
        $departments = Department::withCount('users')->get();

        return response()->json(DepartmentResource::collection($departments));
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        return response()->json(new DepartmentResource($department), 201);
    }

    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        $department = Department::findOrFail($id);
        $department->update($request->validated());

        return response()->json(new DepartmentResource($department));
    }

    public function destroy(int $id): JsonResponse
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->json(['message' => 'Department deleted.']);
    }
}
