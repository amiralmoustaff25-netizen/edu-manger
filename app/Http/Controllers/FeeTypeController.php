<?php

namespace App\Http\Controllers;

use App\Models\FeeType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeTypeController extends Controller
{
    public function index(): View
    {
        // Voir InvoiceController::index() — aligné sur la permission dédiée déjà utilisée par
        // le menu (config/sidebar.php) pour cette entrée.
        $this->authorize('voir-types-frais');

        $feeTypes = FeeType::all();

        return view('accounting.fee-types.index', compact('feeTypes'));
    }

    public function create(): View
    {
        $this->authorize('creer-type-frais');

        return view('accounting.fee-types.create');
    }

    public function store(Request $request)
    {
        $this->authorize('creer-type-frais');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_recurring' => 'boolean',
        ]);

        FeeType::create($validated);

        return redirect()->route('fee-types.index')
            ->with('success', 'Type de frais créé avec succès.');
    }

    public function edit(FeeType $feeType): View
    {
        $this->authorize('modifier-type-frais', $feeType);

        return view('accounting.fee-types.edit', compact('feeType'));
    }

    public function update(Request $request, FeeType $feeType)
    {
        $this->authorize('modifier-type-frais', $feeType);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_recurring' => 'boolean',
        ]);

        $feeType->update($validated);

        return redirect()->route('fee-types.index')
            ->with('success', 'Type de frais modifié avec succès.');
    }

    public function destroy(FeeType $feeType)
    {
        $this->authorize('supprimer-type-frais', $feeType);

        // ARC-02 : la FK classroom_fees.fee_type_id est en cascade au niveau
        // base — sans ce contrôle applicatif, supprimer un type de frais
        // effacerait silencieusement toute la grille tarifaire (et les
        // factures) qui en dépendent, pour toutes les classes/années.
        if ($feeType->classroomFees()->withTrashed()->exists() || $feeType->invoiceItems()->exists()) {
            return redirect()->route('fee-types.index')
                ->withErrors(['fee_type' => 'Impossible de supprimer ce type de frais : il est utilisé par une grille tarifaire ou une facture existante.']);
        }

        $feeType->delete();

        return redirect()->route('fee-types.index')
            ->with('success', 'Type de frais supprimé avec succès.');
    }
}
