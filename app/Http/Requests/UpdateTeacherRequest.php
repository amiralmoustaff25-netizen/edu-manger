<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('modifier-professeur');
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('specialites') && is_string($this->specialites)) {
            $specialites = collect(explode(',', $this->specialites))
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->all();

            $this->merge(['specialites' => $specialites]);
        }
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')
                    ->ignore($this->route('teacher')->user->id ?? null)
                    ->where(fn ($q) => $q->whereNull('deleted_at')),
            ],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['required', 'string', 'max:255'],
            'sexe' => ['required', Rule::in(['masculin', 'feminin'])],
            'nationalite' => ['required', 'string', 'max:255'],
            'diplomes' => ['required', 'string'],
            'etablissements_formation' => ['required', 'string'],
            'statut' => ['required', Rule::in(['fonctionnaire', 'contractuel', 'vacataire'])],
            'date_recrutement' => ['required', 'date'],
            'specialites' => ['required', 'array', 'min:1'],
            'specialites.*' => ['required', 'string', 'max:255'],
            'filiation' => ['required', 'string'],
            'contact_urgence_nom' => ['required', 'string', 'max:255'],
            'contact_urgence_tel' => ['required', 'string', 'max:20'],
            'rib' => ['nullable', 'string', 'max:255'],
            'nombre_heures_semaine' => ['nullable', 'integer', 'min:0'],
            'telephone' => ['nullable', 'string', 'max:20'],
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
            'nationalite.required' => 'La nationalité est obligatoire.',
            'diplomes.required' => 'Les diplômes sont obligatoires.',
            'etablissements_formation.required' => 'Les établissements de formation sont obligatoires.',
            'statut.required' => 'Le statut est obligatoire.',
            'date_recrutement.required' => 'La date de recrutement est obligatoire.',
            'specialites.required' => 'Les spécialités sont obligatoires.',
            'specialites.*.required' => 'Chaque spécialité doit être renseignée.',
            'filiation.required' => 'La filiation est obligatoire.',
            'contact_urgence_nom.required' => 'Le nom du contact d’urgence est obligatoire.',
            'contact_urgence_tel.required' => 'Le téléphone du contact d’urgence est obligatoire.',
        ];
    }
}
