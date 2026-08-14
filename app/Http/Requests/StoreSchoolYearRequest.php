<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('creer-annee-scolaire');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'year_string' => [
                'required',
                'string',
                'unique:school_years,year_string',
                'regex:/^\d{4}-\d{4}$/',
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
            'duplicate_from_id' => ['nullable', 'integer', 'exists:school_years,id'],
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
