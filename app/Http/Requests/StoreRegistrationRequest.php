<?php

namespace App\Http\Requests;

use App\Models\SchoolYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $notTrashed = fn ($query) => $query->whereNull('deleted_at');
        $activeYearId = SchoolYear::where('is_active', true)->value('id');

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where($notTrashed)],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['required', 'string', 'max:255'],
            'sexe' => ['required', 'in:M,F'],
            'cycle' => ['required', 'in:primaire,college,lycee'],
            // La classe doit appartenir à l'année scolaire active : sinon une
            // inscription se retrouverait rattachée à une classe d'une autre année.
            // Si aucune année n'est active, on laisse passer ce champ tel quel :
            // le contrôleur renvoie déjà un message dédié plus clair dans ce cas.
            'classroom_id' => [
                'required',
                $activeYearId
                    ? Rule::exists('classrooms', 'id')->where('school_year_id', $activeYearId)
                    : 'exists:classrooms,id',
            ],
            'telephone' => ['nullable', 'string', 'max:20'],
            'nationalite' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'registration_fee_paid' => ['required', 'numeric', 'min:0'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'options' => ['nullable', 'array'],
            'options.*' => ['boolean'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'medical_notes' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'parents' => ['sometimes', 'array'],
            'parents.*.parent_id' => ['nullable', 'exists:parents,id'],
            'parents.*.lien_parente' => ['nullable', 'string', 'max:255'],
            'parents.*.est_responsable_financier' => ['nullable', 'boolean'],
            'parents.*.est_contact_urgence' => ['nullable', 'boolean'],
            'parent_nom' => ['nullable', 'required_with:parent_prenom,parent_email', 'string', 'max:255'],
            'parent_prenom' => ['nullable', 'required_with:parent_nom', 'string', 'max:255'],
            'parent_email' => [
                'nullable', 'required_with:parent_nom', 'email', 'max:255',
                Rule::unique('users', 'email')->where($notTrashed),
                Rule::unique('parents', 'email')->where($notTrashed),
            ],
            'parent_telephone' => ['nullable', 'string', 'max:20'],
            'parent_adresse' => ['nullable', 'string', 'max:500'],
            'parent_profession' => ['nullable', 'string', 'max:255'],
            'parent_lien_parente' => ['nullable', 'in:Pere,Mere,Tuteur,Tutrice,Autre'],
            'parent_est_responsable_financier' => ['nullable', 'boolean'],
            'parent_est_contact_urgence' => ['nullable', 'boolean'],
        ];
    }
}
