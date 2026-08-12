<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * La Policy du contrôleur garantit déjà l'autorisation.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:complet,partiel'],
            'remaining_balance' => ['nullable', 'numeric', 'min:0'],
            'month' => ['required', 'string'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'payment_type' => ['required', 'string'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
