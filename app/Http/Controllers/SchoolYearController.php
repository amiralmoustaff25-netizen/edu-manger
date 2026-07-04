<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SchoolYearController extends Controller
{
    /**
     * Liste des années scolaires.
     */
    public function index(): View
    {
        $this->authorize('voir-annees-scolaires');

        $schoolYears = SchoolYear::orderBy('year_string', 'desc')->get();

        return view('school_years.index', compact('schoolYears'));
    }

    /**
     * Enregistrement d'une nouvelle année scolaire.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('creer-annee-scolaire');

        $validated = $request->validate([
            'year_string' => [
                'required',
                'string',
                'unique:school_years,year_string',
                'regex:/^\d{4}-\d{4}$/', // Format: 2025-2026
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        $schoolYear = DB::transaction(function () use ($validated) {
            $validated['status'] = ($validated['is_active'] ?? false) ? 'active' : 'upcoming';

            return SchoolYear::create($validated);
        });

        return redirect()
            ->route('school-years.index')
            ->with('success', 'Année scolaire ' . $schoolYear->year_string . ' créée avec succès.');
    }

    /**
     * Activation d'une année scolaire.
     * Désactive automatiquement les autres années.
     */
    public function activate(SchoolYear $schoolYear): RedirectResponse
    {
        $this->authorize('activer-annee-scolaire', $schoolYear);

        if ($schoolYear->is_active) {
            return redirect()
                ->back()
                ->with('info', 'L\'année scolaire ' . $schoolYear->year_string . ' est déjà active.');
        }

        DB::transaction(function () use ($schoolYear) {
            // Désactiver toutes les autres années
            SchoolYear::where('id', '!=', $schoolYear->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'status' => 'completed']);

            // Activer l'année sélectionnée
            $schoolYear->update(['is_active' => true, 'status' => 'active']);
        });

        return redirect()
            ->back()
            ->with('success', 'L\'année scolaire ' . $schoolYear->year_string . ' est maintenant active.');
    }

    /**
     * Suppression d'une année scolaire.
     * Vérifie les dépendances avant suppression.
     */
    public function destroy(SchoolYear $schoolYear): RedirectResponse
    {
        $this->authorize('supprimer-annee-scolaire', $schoolYear);

        // Empêcher la suppression de l'année active
        if ($schoolYear->is_active) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer l\'année scolaire active. Veuillez d\'abord activer une autre année.');
        }

        // Vérifier s'il y a des classes rattachées
        $classroomsCount = Classroom::where('school_year_id', $schoolYear->id)->count();

        // Vérifier s'il y a des inscriptions rattachées
        $registrationsCount = Registration::where('school_year_id', $schoolYear->id)->count();

        if ($classroomsCount > 0 || $registrationsCount > 0) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer cette année scolaire : ' .
                    ($classroomsCount > 0 ? $classroomsCount . ' classe(s) ' : '') .
                    ($classroomsCount > 0 && $registrationsCount > 0 ? 'et ' : '') .
                    ($registrationsCount > 0 ? $registrationsCount . ' inscription(s) ' : '') .
                    'y sont rattachées. Veuillez les transférer ou les supprimer d\'abord.');
        }

        $yearString = $schoolYear->year_string;

        DB::transaction(function () use ($schoolYear) {
            $schoolYear->delete();
        });

        return redirect()
            ->back()
            ->with('success', 'L\'année scolaire ' . $yearString . ' a été supprimée avec succès.');
    }
}