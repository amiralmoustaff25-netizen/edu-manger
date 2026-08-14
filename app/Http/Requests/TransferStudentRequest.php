<?php

namespace App\Http\Requests;

use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le second argument ($student) ne restreint PAS réellement l'action à cet
        // élève précis : UserPolicy ne définit aucune méthode transfererEleve(), et
        // Spatie court-circuite via Gate::before dès que l'acteur possède la
        // permission nommée 'transferer-eleve' (globale, indépendante de la cible).
        // Seuls super-admin/admin la détiennent aujourd'hui, donc sans impact actif —
        // mais si cette permission était un jour accordée à un rôle à périmètre
        // restreint (ex: professeur limité à sa classe), ce check ne le bloquerait pas.
        return $this->user()->can('transferer-eleve', $this->route('student'));
    }

    public function rules(): array
    {
        return [
            'registration_id' => ['required', 'exists:registrations,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_id.required' => 'L\'inscription est obligatoire.',
            'registration_id.exists' => 'Cette inscription n\'existe pas.',
            'classroom_id.required' => 'La classe est obligatoire.',
            'classroom_id.exists' => 'Cette classe n\'existe pas.',
        ];
    }

    public function withValidator($validator): void
    {
        // Le transfert change classroom_id sans jamais changer school_year_id sur
        // l'inscription (voir StudentController::transfer()) : une classe d'une
        // autre année scolaire que celle de l'inscription rendrait donc les deux
        // champs incohérents entre eux.
        $validator->after(function ($validator) {
            $registration = Registration::find($this->input('registration_id'));
            $classroom = \App\Models\Classroom::find($this->input('classroom_id'));

            if ($registration && $classroom && $classroom->school_year_id !== $registration->school_year_id) {
                $validator->errors()->add('classroom_id', "Cette classe n'appartient pas à l'année scolaire de l'inscription à transférer.");
            }
        });
    }
}
