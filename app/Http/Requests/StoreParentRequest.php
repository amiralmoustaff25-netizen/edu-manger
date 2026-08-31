<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('creer-parent');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Un email d'archive (parent ou compte utilisateur archivé) ne doit pas bloquer sa
        // réutilisation — même correctif que StoreUserRequest/StoreTeacherRequest/
        // StoreRegistrationRequest, jusqu'ici absent ici (incohérence : la même situation
        // était bloquée à tort selon le formulaire utilisé pour créer le compte).
        $notTrashed = fn ($query) => $query->whereNull('deleted_at');

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('parents', 'email')->where($notTrashed),
                Rule::unique('users', 'email')->where($notTrashed),
            ],
            'telephone' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string'],
            'profession' => ['nullable', 'string', 'max:255'],
            'statut' => ['required', Rule::in(['actif', 'en_attente_activation', 'archive'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être actif, en_attente_activation ou archive.',
        ];
    }
}
