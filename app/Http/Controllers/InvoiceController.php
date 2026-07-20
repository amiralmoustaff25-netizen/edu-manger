<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Registration;
use App\Models\FeeType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('voir-comptabilite');

        $query = Invoice::with(['registration.user', 'registration.classroom']);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('registration.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->string('year'));
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        return view('accounting.invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        $this->authorize('creer-facture');

        $registrations = Registration::with(['user', 'classroom'])
            ->where('status', 'active')
            ->get();
        
        $feeTypes = FeeType::all();

        return view('accounting.invoices.create', compact('registrations', 'feeTypes'));
    }

    public function store(Request $request)
    {
        $this->authorize('creer-facture');

        $validated = $request->validate([
            'registration_id' => 'required|exists:registrations,id',
            'due_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.fee_type_id' => 'required|exists:fee_types,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $registration = Registration::findOrFail($validated['registration_id']);

        // Génération du numéro de facture
        $invoiceNumber = 'FAC-' . now()->year . '-' . str_pad(Invoice::whereYear('created_at', now()->year)->count() + 1, 4, '0', STR_PAD_LEFT);

        $totalAmount = collect($validated['items'])->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });

        $invoice = Invoice::create([
            'registration_id' => $registration->id,
            'invoice_number' => $invoiceNumber,
            'total_amount' => $totalAmount,
            'remaining_balance' => $totalAmount,
            'due_date' => $validated['due_date'],
            'status' => 'pending',
            'issued_at' => now(),
        ]);

        // Création des lignes de facture
        foreach ($validated['items'] as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_type_id' => $item['fee_type_id'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Facture créée avec succès.');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('voir-comptabilite');

        $invoice->load(['registration.user', 'registration.classroom', 'items.feeType', 'payments']);

        return view('accounting.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('modifier-facture', $invoice);

        $invoice->load(['registration.user', 'registration.classroom', 'items.feeType']);
        $registrations = Registration::with(['user', 'classroom'])
            ->where('status', 'active')
            ->get();
        $feeTypes = FeeType::all();

        return view('accounting.invoices.edit', compact('invoice', 'registrations', 'feeTypes'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('modifier-facture', $invoice);

        $validated = $request->validate([
            'due_date' => 'required|date',
            'status' => 'required|in:pending,paid,overdue,cancelled',
            'remaining_balance' => 'nullable|numeric|min:0',
        ]);

        $invoice->update($validated);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Facture modifiée avec succès.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('supprimer-facture', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Facture supprimée avec succès.');
    }

    public function exportPdf(Invoice $invoice)
    {
        $this->authorize('voir-comptabilite');

        $invoice->load(['registration.user', 'registration.classroom', 'items.feeType']);

        $pdf = \PDF::loadView('accounting.invoices.pdf', compact('invoice'));
        
        return $pdf->download('facture-' . $invoice->invoice_number . '.pdf');
    }
}
