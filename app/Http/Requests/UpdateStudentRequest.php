<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 'voir-detail-eleve' est aussi accordée à un parent pour son propre enfant
        // (consultation) — la vérifier seule ici laisserait la route de modification
        // reposer uniquement sur le middleware role:super-admin|admin de routes/web.php.
        // Vérification explicite du rôle en plus, pour que ce Form Request reste sûr
        // même si ce middleware venait à changer.
        return $this->user()->hasAnyRole(['super-admin', 'admin'])
            && $this->user()->can('voir-detail-eleve', $this->route('student'));
    }

    public function rules(): array
    {
        // La classe doit appartenir à la même année scolaire que l'inscription
        // modifiée (voir StudentController::update()) : sinon classroom_id et
        // school_year_id de l'inscription divergeraient silencieusement.
        $registrationYearId = $this->route('student')->latestRegistration?->school_year_id;

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('student')->id ?? null)->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['required', 'string', 'max:255'],
            'sexe' => ['required', Rule::in(['M', 'F'])],
            'cycle' => ['required', Rule::in(['primaire', 'college', 'lycee'])],
            'classroom_id' => [
                'required',
                $registrationYearId
                    ? Rule::exists('classrooms', 'id')->where('school_year_id', $registrationYearId)
                    : 'exists:classrooms,id',
            ],
            'parents' => ['sometimes', 'array'],
            'parents.*.parent_id' => ['nullable', 'exists:parents,id'],
            // parent_user.lien_parente est un enum strict côté MySQL (Pere/Mere/Tuteur/
            // Tutrice/Autre) — voir StoreRegistrationRequest pour le même correctif.
            'parents.*.lien_parente' => ['nullable', Rule::in(['Pere', 'Mere', 'Tuteur', 'Tutrice', 'Autre'])],
            'parents.*.est_responsable_financier' => ['nullable', 'boolean'],
            'parents.*.est_contact_urgence' => ['nullable', 'boolean'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'nationalite' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'medical_notes' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'registration_fee_paid' => ['nullable', 'numeric', 'min:0'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'options' => ['nullable', 'array'],
            'options.*' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:2048'], // 2MB max
            'supprimer_photo' => ['nullable', 'boolean'], // Option to delete photo
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'email.required' => 'L’adresse email est obligatoire.',
            'email.email' => 'L’adresse email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'lieu_naissance.required' => 'Le lieu de naissance est obligatoire.',
            'sexe.required' => 'Le sexe est obligatoire.',
            'cycle.required' => 'Le cycle est obligatoire.',
            'classroom_id.required' => 'La classe est obligatoire.',
            'classroom_id.exists' => 'La classe sélectionnée n’existe pas.',
        ];
    }
}
