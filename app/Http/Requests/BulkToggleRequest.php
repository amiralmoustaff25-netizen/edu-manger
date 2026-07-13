<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chapter_ids' => ['required', 'array'],
            'chapter_ids.*' => ['exists:program_chapters,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
