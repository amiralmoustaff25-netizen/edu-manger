<?php

namespace App\Http\Controllers;

use App\Models\FeeType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('voir-comptabilite');

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

        $feeType->delete();

        return redirect()->route('fee-types.index')
            ->with('success', 'Type de frais supprimé avec succès.');
    }
}
