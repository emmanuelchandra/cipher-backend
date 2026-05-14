<?php

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize two cases that arrive from face-api.js:
     *
     * 1. Float32Array serialized as JSON object {"0": x, "1": y, ...}
     *    → convert to flat indexed array [x, y, ...]
     *
     * 2. Single flat descriptor sent as `descriptor` (not nested)
     *    → wrap in outer array so the controller always receives [[...]]
     */
    protected function prepareForValidation(): void
    {
        $raw = $this->input('descriptor');
        if ($raw === null) {
            return;
        }

        // Case 1: JSON object with numeric string keys (Float32Array)
        if (is_array($raw) && !array_is_list($raw)) {
            ksort($raw);
            $raw = array_values($raw);
        }

        // Case 2: single flat descriptor (first element is a number, not an array)
        if (is_array($raw) && isset($raw[0]) && !is_array($raw[0])) {
            $raw = [$raw];
        }

        $this->merge(['descriptor' => $raw]);
    }

    public function rules(): array
    {
        return [
            // Optional: admin can register face for another user; defaults to auth user
            'user_id'        => ['sometimes', 'exists:users,id'],
            // One or more face samples
            'descriptor'     => ['required', 'array', 'min:1'],
            'descriptor.*'   => ['required', 'array', 'min:1'],
            'descriptor.*.*' => ['required', 'numeric'],
        ];
    }
}
