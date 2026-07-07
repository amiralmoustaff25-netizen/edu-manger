<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('enregistrer-paiement');
    }

    public function rules(): array
    {
        return [
            'registration_id' => ['required', 'exists:registrations,id'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'month' => ['required', 'string', 'max:50'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_type' => ['nullable', 'string', 'max:50'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_id.required' => 'L\'inscription est obligatoire.',
            'amount_paid.required' => 'Le montant est obligatoire.',
            'amount_paid.numeric' => 'Le montant doit être un nombre.',
            'amount_paid.min' => 'Le montant ne peut pas être négatif.',
            'month.required' => 'Le mois est obligatoire.',
        ];
    }
}
