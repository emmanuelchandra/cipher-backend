<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaceDescriptorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'descriptor' => $this->descriptor,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
