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
            'pedagogical_assignment_id' => ['required', 'exists:pedagogical_assignments,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'chapters' => ['required', 'array', 'min:1'],
            'chapters.*.titre' => ['required', 'string', 'max:255'],
            'chapters.*.type' => ['required', 'in:chapitre,lecon,sous_partie'],
            'chapters.*.volume_horaire_prevu' => ['required', 'numeric', 'min:0.5'],
            'chapters.*.academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'chapters.*.planned_at' => ['nullable', 'date'],
            'chapters.*.children' => ['required', 'array', 'min:1'],
            'chapters.*.children.*.titre' => ['required', 'string', 'max:255'],
            'chapters.*.children.*.volume_horaire_prevu' => ['required', 'numeric', 'min:0.5'],
            'chapters.*.children.*.academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'chapters.*.children.*.planned_at' => ['nullable', 'date'], 
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
