<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleAssignmentRequest extends FormRequest
{
    private const AVAILABLE_ROLES = [
        'super-admin', 'admin', 'manager-comptable', 'comptable', 'surveillant',
        'professeur', 'parent', 'eleve',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::in(self::AVAILABLE_ROLES)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'confirm_super_admin' => ['nullable', 'in:1'],
        ];
    }
}
