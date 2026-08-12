<?php

namespace App\Http\Requests;

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
            'grades.*.valeur' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'grades.*.appreciation' => ['nullable', 'string'],
        ];
    }
}
