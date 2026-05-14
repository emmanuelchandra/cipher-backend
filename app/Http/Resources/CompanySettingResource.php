<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanySettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'company_address' => $this->company_address,
            'company_stamp_url' => $this->company_stamp_path
                ? Storage::disk('r2')->url($this->company_stamp_path)
                : null,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
