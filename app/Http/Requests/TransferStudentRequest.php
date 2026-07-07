<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transferer-eleve', $this->route('student'));
    }

    public function rules(): array
    {
        return [
            'registration_id' => ['required', 'exists:registrations,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_id.required' => 'L\'inscription est obligatoire.',
            'registration_id.exists' => 'Cette inscription n\'existe pas.',
            'classroom_id.required' => 'La classe est obligatoire.',
            'classroom_id.exists' => 'Cette classe n\'existe pas.',
        ];
    }
}
