<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramAnnualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            $checkDepth = function (array $items, int $depth = 1) use (&$checkDepth, $validator): void {
                foreach ($items as $item) {
                    if ($depth > 3) {
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
