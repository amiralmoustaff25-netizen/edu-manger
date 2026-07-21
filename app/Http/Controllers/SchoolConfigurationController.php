<?php

namespace App\Http\Controllers;

use App\Models\SchoolConfiguration;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolConfigurationController extends Controller
{
    public function index(): View
    {
        $this->authorize('super-admin|admin');

        $config = SchoolConfiguration::current();

        return view('settings.school.index', compact('config'));
    }

    public function updateSchoolInfo(Request $request)
    {
        $this->authorize('super-admin|admin');

        $request->validate([
            'school_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
        ]);

        $config = SchoolConfiguration::current();
        $config->update($request->only(['school_name', 'address', 'phone', 'email', 'website']));

        return redirect()->back()->with('success', 'Informations de l\'école mises à jour avec succès.');
    }

    public function updateBankInfo(Request $request)
    {
        $this->authorize('super-admin|admin');

        $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:34',
            'swift' => 'nullable|string|max:11',
        ]);

        $config = SchoolConfiguration::current();
        $config->update($request->only(['bank_name', 'account_number', 'iban', 'swift']));

        return redirect()->back()->with('success', 'Informations bancaires mises à jour avec succès.');
    }

    public function updateAccountingSettings(Request $request)
    {
        $this->authorize('manager-comptable|comptable|super-admin|admin');

        $request->validate([
            'overpayment_mode' => 'required|in:change,credit',
            'sequential_payment_rule' => 'required|boolean',
            'allow_future_payment' => 'required|boolean',
        ]);

        $config = SchoolConfiguration::current();
        $config->update($request->only(['overpayment_mode', 'sequential_payment_rule', 'allow_future_payment']));

        return redirect()->back()->with('success', 'Paramètres comptables mis à jour avec succès.');
    }

    public function completeConfiguration()
    {
        $this->authorize('super-admin|admin');

        $config = SchoolConfiguration::current();
        $config->markAsConfigured();

        return redirect()->route('dashboard')->with('success', 'Configuration de l\'école terminée avec succès!');
    }
}
