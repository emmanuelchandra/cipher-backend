<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UploadStampRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stamp' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }
}
