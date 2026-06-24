<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    /**
     * Protection : Seuls les Admin et Super-Admin peuvent gérer les années.
     */
    public function __construct()
    {
        $this->middleware(['role:Super-Admin|Admin']);
    }

    // Liste des années scolaires
    public function index()
    {
        $schoolYears = SchoolYear::orderBy('year_string', 'desc')->get();
        return view('school_years.index', compact('schoolYears'));
    }

    // Enregistrement d'une nouvelle année
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year_string' => 'required|string|unique:school_years,year_string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        // Définir le statut automatiquement
        $validated['status'] = $validated['is_active'] ?? false ? 'active' : 'upcoming';

        SchoolYear::create($validated);

        return redirect()->route('school-years.index')
            ->with('success', 'Année scolaire créée avec succès.');
    }

    // Activation d'une année (désactive automatiquement les autres via le Modèle)
    public function activate(SchoolYear $schoolYear)
    {
        $schoolYear->update(['is_active' => true]);

        return redirect()->back()
            ->with('success', 'L\'année ' . $schoolYear->year_string . ' est maintenant active.');
    }

    // Suppression d'une année
    public function destroy(SchoolYear $schoolYear)
    {
        if ($schoolYear->is_active) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer l\'année scolaire active.');
        }

        $schoolYear->delete();

        return redirect()->back()
            ->with('success', 'Année scolaire supprimée.');
    }
}