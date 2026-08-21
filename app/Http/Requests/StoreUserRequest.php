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

    protected function prepareForValidation(): void
    {
        // Même format libre "séparé par des virgules" que StoreTeacherRequest.
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
        // Le rôle super-admin n'est jamais attribuable à la création.
        $isProfesseur = $this->input('role') === 'professeur';

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            // Email obligatoire pour un professeur (compte utilisé au quotidien), facultatif
            // pour les autres rôles créés ici — comportement inchangé pour ceux-ci.
            'email' => [$isProfesseur ? 'required' : 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'telephone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(UserRoles::CREATABLE_VIA_USER_FORM)],
            'is_active' => ['boolean'],

            // Fiche professeur (Teacher) : mêmes règles que StoreTeacherRequest, affichées
            // dynamiquement dans le formulaire quand role=professeur est sélectionné — mais
            // toujours présents dans le HTML (juste masqués via x-show), donc soumis vides
            // pour tout autre rôle. 'nullable' est indispensable en plus de requiredIf : sans
            // lui, une chaîne vide fait quand même échouer 'date'/Rule::in/etc., bloquant la
            // création de tout compte non-professeur (ex. surveillant) à cause de champs que
            // l'utilisateur ne voit même pas.
            'date_naissance' => [Rule::requiredIf($isProfesseur), 'nullable', 'date'],
            'lieu_naissance' => [Rule::requiredIf($isProfesseur), 'nullable', 'string', 'max:255'],
            'sexe' => [Rule::requiredIf($isProfesseur), 'nullable', Rule::in(['masculin', 'feminin'])],
            'nationalite' => [Rule::requiredIf($isProfesseur), 'nullable', 'string', 'max:255'],
            'diplomes' => [Rule::requiredIf($isProfesseur), 'nullable', 'string'],
            'etablissements_formation' => [Rule::requiredIf($isProfesseur), 'nullable', 'string'],
            'statut' => [Rule::requiredIf($isProfesseur), 'nullable', Rule::in(['fonctionnaire', 'contractuel', 'vacataire'])],
            'date_recrutement' => [Rule::requiredIf($isProfesseur), 'nullable', 'date'],
            'specialites' => [Rule::requiredIf($isProfesseur), 'nullable', 'array', 'min:1'],
            'specialites.*' => ['required', 'string', 'max:255'],
            'filiation' => [Rule::requiredIf($isProfesseur), 'nullable', 'string'],
            'contact_urgence_nom' => [Rule::requiredIf($isProfesseur), 'nullable', 'string', 'max:255'],
            'contact_urgence_tel' => [Rule::requiredIf($isProfesseur), 'nullable', 'string', 'max:20'],
            'nombre_heures_semaine' => ['nullable', 'integer', 'min:0'],
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
            'email.required' => 'L’adresse email est obligatoire pour un compte professeur.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.date' => 'La date de naissance doit être une date valide.',
            'lieu_naissance.required' => 'Le lieu de naissance est obligatoire.',
            'sexe.required' => 'Le sexe est obligatoire.',
            'sexe.in' => 'Le sexe doit être masculin ou féminin.',
            'nationalite.required' => 'La nationalité est obligatoire.',
            'diplomes.required' => 'Les diplômes sont obligatoires.',
            'etablissements_formation.required' => 'Les établissements de formation sont obligatoires.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être fonctionnaire, contractuel ou vacataire.',
            'date_recrutement.required' => 'La date de recrutement est obligatoire.',
            'specialites.required' => 'Les spécialités sont obligatoires.',
            'specialites.*.required' => 'Chaque spécialité doit être renseignée.',
            'filiation.required' => 'La filiation est obligatoire.',
            'contact_urgence_nom.required' => 'Le nom du contact d’urgence est obligatoire.',
            'contact_urgence_tel.required' => 'Le téléphone du contact d’urgence est obligatoire.',
        ];
    }
}
