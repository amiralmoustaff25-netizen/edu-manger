<?php

namespace App\Http\Requests;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Services\GradeCalculationService;
use Illuminate\Foundation\Http\FormRequest;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'type_evaluation' => ['required', 'string'],
            'periode' => ['required', 'string'],
            'grades' => ['required', 'array'],
            'grades.*.user_id' => ['required', 'exists:users,id'],
            'grades.*.valeur' => ['nullable', 'numeric', 'min:0', 'max:'.$this->resolveMaxValeur()],
            'grades.*.appreciation' => ['nullable', 'string'],
        ];
    }

    /**
     * Note maximale acceptée : 20 partout, sauf en primaire quand un barème a été
     * configuré pour cette matière (système "sunuBulletin", ex. Mathématiques /80) — voir
     * GradeCalculationService::resolveBareme(), même source que le calcul de moyenne, pour
     * qu'une note jamais validable côté serveur ne puisse jamais dépasser silencieusement
     * ce qui sera réellement utilisé pour la moyenne générale.
     */
    private function resolveMaxValeur(): float
    {
        $classroom = Classroom::find($this->input('classroom_id'));
        $matiere = Matiere::find($this->input('matiere_id'));

        if (! $classroom || ! $matiere) {
            return 20;
        }

        return app(GradeCalculationService::class)->resolveBareme($matiere, $classroom, $classroom->school_year_id);
    }
}
