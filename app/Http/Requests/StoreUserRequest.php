<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    private const ROLES = [
        'super-admin',
        'admin',
        'manager-comptable',
        'comptable',
        'surveillant',
    ];

    public function authorize(): bool
    {
        return $this->user()->can('creer-utilisateur');
    }

    public function rules(): array
    {
        $rules = [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(self::ROLES)],
            'is_active' => ['boolean'],
        ];

        // Validation conditionnelle pour le rôle professeur
        if ($this->input('role') === 'professeur') {
            $rules['date_naissance'] = ['nullable', 'date'];
            $rules['lieu_naissance'] = ['nullable', 'string', 'max:255'];
            $rules['sexe'] = ['nullable', 'in:masculin,feminin'];
            $rules['nationalite'] = ['nullable', 'string', 'max:100'];
            $rules['statut'] = ['nullable', 'in:fonctionnaire,contractuel,vacataire'];
            $rules['diplomes'] = ['nullable', 'string'];
            $rules['specialites'] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'email.email' => 'L\'email doit être valide.',
            'telephone.max' => 'Le numéro de téléphone ne doit pas dépasser 20 caractères.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle sélectionné est invalide.',
            'date_naissance.date' => 'La date de naissance doit être une date valide.',
            'sexe.in' => 'Le sexe doit être masculin ou féminin.',
            'statut.in' => 'Le statut doit être fonctionnaire, contractuel ou vacataire.',
        ];
    }
}
