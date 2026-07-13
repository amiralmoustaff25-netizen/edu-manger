<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramAnnualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'subject_id' => ['required', 'exists:matieres,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
            'teacher_id' => ['required', 'exists:users,id'],
            'chapters' => ['required', 'array'],
            'chapters.*.titre' => ['required', 'string', 'max:255'],
            'chapters.*.type' => ['required', 'in:chapitre,lecon,sous_partie'],
            'chapters.*.volume_horaire_prevu' => ['required', 'numeric', 'min:0.5'],
            'chapters.*.children' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $chapters = $this->input('chapters', []);
            $maxDepth = 3;

            $checkDepth = function (array $items, int $depth = 1) use (&$checkDepth, $validator, $maxDepth): void {
                foreach ($items as $item) {
                    if ($depth > $maxDepth) {
                        $validator->errors()->add('chapters', 'La profondeur maximale autorisée est de 3 niveaux.');

                        return;
                    }

                    if (! empty($item['children'])) {
                        $checkDepth($item['children'], $depth + 1);
                    }
                }
            };

            $checkDepth($chapters);
        });
    }
}
