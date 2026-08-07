<?php

namespace App\Http\Requests;

use App\Support\UserRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('creer-utilisateur');
    }

    public function rules(): array
    {
        // Ni le rôle professeur (fiche dédiée via le module Professeurs) ni le rôle
        // super-admin (jamais attribuable à la création) ne sont proposés ici.
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(UserRoles::CREATABLE_VIA_USER_FORM)],
            'is_active' => ['boolean'],
        ];
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
