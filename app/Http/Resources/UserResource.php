<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'signature_url' => $this->signature_path
                ? \Storage::disk('r2')->url($this->signature_path)
                : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
