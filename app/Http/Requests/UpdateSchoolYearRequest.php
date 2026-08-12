<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('modifier-annee-scolaire');
    }

    public function rules(): array
    {
        return [
            'year_string' => [
                'required',
                'string',
                Rule::unique('school_years', 'year_string')->ignore($this->route('school_year')),
                'regex:/^\d{4}-\d{4}$/',
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'year_string.required' => 'L\'année scolaire est obligatoire.',
            'year_string.unique' => 'Cette année scolaire existe déjà.',
            'year_string.regex' => 'Le format doit être AAAA-AAAA (ex: 2025-2026).',
            'end_date.after' => 'La date de fin doit être après la date de début.',
        ];
    }
}
