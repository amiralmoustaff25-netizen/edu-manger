<?php

namespace App\Http\Requests;

use App\Support\UserRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        // Seul un super-admin peut modifier un compte super-admin (protège contre
        // la désactivation/dépossession d'un super-admin par un simple admin).
        if ($target->hasRole('super-admin') && ! $this->user()->hasRole('super-admin')) {
            return false;
        }

        return $this->user()->can('modifier-utilisateur', $target);
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            // Le formulaire (resources/views/users/_form.blade.php, partagé avec la
            // création) soumet nom/prenom, pas un champ "name" unique.
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'role' => ['required', Rule::in(UserRoles::assignableBy($this->user()))],
            'contract_started_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            $newRole = $this->input('role');

            if ($newRole === 'professeur' && $user->role !== 'professeur') {
                $validator->errors()->add(
                    'role',
                    "Impossible d'attribuer le rôle professeur depuis ce formulaire : créez ou convertissez ce compte via le module Professeurs, qui collecte les informations obligatoires (statut, diplômes, filiation, etc.)."
                );
            }

            if ($user->role === 'professeur' && $newRole !== 'professeur'
                && $user->teacher?->pedagogicalAssignments()->where('is_active', true)->exists()) {
                $validator->errors()->add(
                    'role',
                    'Ce compte a des affectations pédagogiques actives. Retirez-les depuis le module Professeurs avant de changer son rôle.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle sélectionné est invalide.',
        ];
    }
}
