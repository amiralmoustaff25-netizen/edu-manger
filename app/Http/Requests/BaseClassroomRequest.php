<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class BaseClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => ['required', 'string'],
            'section' => ['nullable', 'string'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'max_students' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $teacherId = $this->input('teacher_id');

                if ($teacherId && ! User::find($teacherId)?->hasRole('professeur')) {
                    $validator->errors()->add('teacher_id', 'L\'utilisateur sélectionné n\'est pas un professeur.');
                }
            },
        ];
    }
}
