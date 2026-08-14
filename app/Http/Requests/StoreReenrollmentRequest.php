<?php

namespace App\Http\Requests;

use App\Models\SchoolYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReenrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $activeYearId = SchoolYear::where('is_active', true)->value('id');

        return [
            'user_id' => ['required', 'exists:users,id'],
            // La classe doit appartenir à l'année scolaire active : sinon une
            // réinscription se retrouverait rattachée à une classe d'une autre année.
            // Si aucune année n'est active, on laisse passer ce champ tel quel :
            // le contrôleur renvoie déjà un message dédié plus clair dans ce cas.
            'classroom_id' => [
                'required',
                $activeYearId
                    ? Rule::exists('classrooms', 'id')->where('school_year_id', $activeYearId)
                    : 'exists:classrooms,id',
            ],
            'registration_fee_paid' => ['required', 'numeric', 'min:0'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'options' => ['sometimes', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
