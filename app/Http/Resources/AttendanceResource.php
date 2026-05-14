<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'check_in' => $this->check_in?->toISOString(),
            'check_out' => $this->check_out?->toISOString(),
            'status' => $this->status,
            'overtime_minutes' => $this->overtime_minutes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
