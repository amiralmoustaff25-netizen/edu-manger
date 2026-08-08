<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'pedagogical_assignment_id' => ['required', 'exists:pedagogical_assignments,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
        ];
    }
}
