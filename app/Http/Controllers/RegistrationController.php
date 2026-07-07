<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function create()
    {
        $students = User::where('role', 'eleve')->get();
        $classrooms = Classroom::all();
        $activeYear = SchoolYear::where('is_active', true)->first();

        if (! $activeYear) {
            return back()->withErrors(['error' => 'Aucune année scolaire active.']);
        }

        return view('registrations.create', compact('students', 'classrooms', 'activeYear'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'registration_fee_paid' => ['required', 'numeric', 'min:0'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
        ]);

        $activeYear = SchoolYear::where('is_active', true)->firstOrFail();

        $alreadyRegistered = Registration::where('user_id', $validated['user_id'])
            ->where('school_year_id', $activeYear->id)
            ->exists();

        if ($alreadyRegistered) {
            return back()->withErrors([
                'user_id' => 'Cet élève est déjà inscrit pour l’année scolaire active.',
            ])->withInput();
        }

        DB::beginTransaction();

        try {
            $validated['matricule'] = $this->generateMatricule();
            $validated['status'] = 'pending';
            $validated['registration_date'] = now()->toDateString();
            $validated['academic_year'] = $activeYear->year_string;
            $validated['school_year_id'] = $activeYear->id;

            Registration::create($validated);

            DB::commit();

            return redirect()->route('dashboard')->with('success', 'Inscription élève réussie.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Erreur lors de l’inscription : '.$e->getMessage(),
            ])->withInput();
        }
    }

    private function generateMatricule(): string
    {
        $sequence = Registration::count() + 1;

        do {
            $matricule = 'EDU-'.date('y').'-'.str_pad($sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Registration::where('matricule', $matricule)->exists());

        return $matricule;
    }
}
