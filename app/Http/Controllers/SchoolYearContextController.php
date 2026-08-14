<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Services\SchoolYearContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolYearContextController extends Controller
{
    /**
     * Change l'année scolaire consultée (en session) et revient à la page courante avec
     * les données de cette année — jamais de changement d'écran, conformément au cahier
     * des charges. N'importe quel utilisateur authentifié avec accès au sélecteur (voir
     * <x-school-year-selector>) peut changer sa propre consultation ; ça ne modifie en
     * rien l'année active de l'établissement.
     */
    public function update(Request $request, SchoolYearContext $context): RedirectResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
        ]);

        $context->set(SchoolYear::findOrFail($validated['school_year_id']));

        return redirect()->back();
    }
}
