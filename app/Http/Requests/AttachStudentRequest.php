<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'lien_parente' => ['required', Rule::in(['Pere', 'Mere', 'Tuteur', 'Tutrice', 'Autre'])],
            'est_responsable_financier' => ['boolean'],
            'est_contact_urgence' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'L\'élève est obligatoire.',
            'user_id.exists' => 'L\'élève sélectionné n\'existe pas.',
            'lien_parente.required' => 'Le lien de parenté est obligatoire.',
            'lien_parente.in' => 'Le lien de parenté doit être Pere, Mere, Tuteur, Tutrice ou Autre.',
        ];
    }
}
