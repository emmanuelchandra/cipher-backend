<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Http\Requests\Settings\UploadStampRequest;
use App\Http\Resources\CompanySettingResource;
use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = CompanySetting::firstOrNew([]);

        return response()->json(new CompanySettingResource($settings));
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = CompanySetting::firstOrNew([]);
        $settings->fill($request->validated());
        $settings->save();

        return response()->json(new CompanySettingResource($settings));
    }

    public function uploadStamp(UploadStampRequest $request): JsonResponse
    {
        $settings = CompanySetting::firstOrNew([]);

        if ($settings->company_stamp_path) {
            Storage::disk('r2')->delete($settings->company_stamp_path);
        }

        $path = $request->file('stamp')->store('stamps', 'r2');

        $settings->company_stamp_path = $path;
        $settings->save();

        return response()->json([
            'message' => 'Company stamp uploaded.',
            'stamp_url' => Storage::disk('r2')->url($path),
        ]);
    }
}
