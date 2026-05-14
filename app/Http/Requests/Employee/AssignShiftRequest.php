<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class AssignShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'effective_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
