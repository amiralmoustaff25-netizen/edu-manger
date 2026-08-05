<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Registration;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function store(Request $request, Registration $registration)
    {
        $this->authorize('gerer-derogations-tarifaires');

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

    public function destroy(Request $request, Discount $discount)
    {
        $this->authorize('gerer-derogations-tarifaires');

        $discount->delete();

        return back()->with('success', 'Dérogation tarifaire supprimée avec succès.');
    }
}
