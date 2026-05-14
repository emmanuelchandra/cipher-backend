<?php

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class FaceCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('descriptor')) {
            $descriptor = $this->input('descriptor');

            if (is_array($descriptor) && !array_is_list($descriptor)) {
                ksort($descriptor);
                $this->merge(['descriptor' => array_values($descriptor)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'descriptor'   => ['required', 'array', 'min:1'],
            'descriptor.*' => ['required', 'numeric'],
        ];
    }
}
