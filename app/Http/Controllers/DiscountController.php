<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Registration;
use App\Services\SchoolYearGuardService;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function store(Request $request, Registration $registration, SchoolYearGuardService $schoolYearGuard)
    {
        $this->authorize('gerer-derogations-tarifaires');

        // Une dérogation modifie ce qui est dû sur l'inscription : elle doit être
        // soumise au même verrou d'année scolaire clôturée que les paiements et la
        // grille tarifaire (voir SchoolYearGuardService), sans quoi les obligations
        // financières d'une année déjà close pourraient être modifiées après coup.
        $schoolYearGuard->assertNotLocked($registration->schoolYear);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return back()->withInput()->with('error', 'Le pourcentage de dérogation ne peut pas dépasser 100%.');
        }

        $registration->discounts()->create([
            ...$validated,
            'applied_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Dérogation tarifaire enregistrée avec succès.');
    }

    public function destroy(Request $request, Discount $discount, SchoolYearGuardService $schoolYearGuard)
    {
        $this->authorize('gerer-derogations-tarifaires');

        $schoolYearGuard->assertNotLocked($discount->registration->schoolYear);

        $discount->delete();

        return back()->with('success', 'Dérogation tarifaire supprimée avec succès.');
    }
}
