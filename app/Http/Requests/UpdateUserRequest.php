<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    private const ROLES = [
        'super-admin',
        'admin',
        'manager-comptable',
        'comptable',
        'professeur',
        'parent',
        'eleve',
    ];

    public function authorize(): bool
    {
        return $this->user()->can('modifier-utilisateur', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'role' => ['required', Rule::in(self::ROLES)],
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
            'name.required' => 'Le nom est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle sélectionné est invalide.',
        ];
    }
}
