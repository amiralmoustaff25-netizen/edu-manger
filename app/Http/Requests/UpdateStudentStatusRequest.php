<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('modifier-statut-eleve', $this->route('student'));
    }

    public function rules(): array
    {
        return [
            'registration_id' => ['required', 'exists:registrations,id'],
            'status' => ['required', Rule::in(['pending', 'active'])],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_id.required' => 'L\'inscription est obligatoire.',
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut doit être pending ou active.',
        ];
    }
}