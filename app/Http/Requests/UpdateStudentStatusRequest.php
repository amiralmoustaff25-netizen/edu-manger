<?php

namespace App\Http\Requests;

use App\Support\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Même limite que TransferStudentRequest::authorize() : $student n'est pas
        // réellement exploité pour restreindre par instance (Gate::before de Spatie
        // court-circuite sur la permission globale 'modifier-statut-eleve'). Voir la
        // note détaillée dans TransferStudentRequest.
        return $this->user()->can('modifier-statut-eleve', $this->route('student'));
    }

    public function rules(): array
    {
        return [
            'registration_id' => ['required', 'exists:registrations,id'],
            'status' => ['required', Rule::in(StudentStatus::ALL)],
            'status_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_id.required' => 'L\'inscription est obligatoire.',
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut sélectionné est invalide.',
        ];
    }
}
