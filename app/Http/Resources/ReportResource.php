<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => new UserResource($this->whenLoaded('employee')),
            'month' => $this->month,
            'year' => $this->year,
            'status' => $this->status,
            'pdf_url' => $this->pdf_path
                ? Storage::disk('r2')->url($this->pdf_path)
                : null,
            'signed_pdf_url' => $this->signed_pdf_path
                ? Storage::disk('r2')->url($this->signed_pdf_path)
                : null,
            'approved_by' => new UserResource($this->whenLoaded('approver')),
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
