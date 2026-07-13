# Module Comptabilité & Paiements - Architecture Technique
**Projet** : EduManager (Laravel 12)
**Date** : 13 juillet 2026

---

## 1. Schéma de Base de Données (Modèle Relationnel)

### Tables principales et leurs relations :

---

### 1.1 Table `fee_types` (Types de Frais)
Définit les types de frais configurables par l'Admin/Manager.

| Colonne | Type | Contrainte | Description |
|---------|------|------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Identifiant unique |
| name | VARCHAR(255) | NOT NULL | Nom du frais (Inscription, Mensualité, Cantine, Transport, Tenue scolaire) |
| description | TEXT | NULL | Description du frais |
| is_recurring | BOOLEAN | DEFAULT FALSE | Indique si le frais est récurrent (ex: mensualité) |
| created_at | TIMESTAMP | NULL | Date de création |
| updated_at | TIMESTAMP | NULL | Date de mise à jour |

---

### 1.2 Table `classroom_fees` (Tarifs des Frais par Classe)
Définit les tarifs associés à chaque type de frais pour une classe et une année scolaire.

| Colonne | Type | Contrainte | Description |
|---------|------|------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Identifiant unique |
| classroom_id | BIGINT UNSIGNED | FK → classrooms.id, NOT NULL | Classe concernée |
| fee_type_id | BIGINT UNSIGNED | FK → fee_types.id, NOT NULL | Type de frais |
| school_year_id | BIGINT UNSIGNED | FK → school_years.id, NOT NULL | Année scolaire |
| amount | DECIMAL(10,2) | NOT NULL | Montant du frais |
| created_at | TIMESTAMP | NULL | Date de création |
| updated_at | TIMESTAMP | NULL | Date de mise à jour |
| **Index** | | | UNIQUE (classroom_id, fee_type_id, school_year_id) |

---

### 1.3 Table `discounts` (Remises)
Gère les remises accordées aux élèves.

| Colonne | Type | Contrainte | Description |
|---------|------|------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Identifiant unique |
| registration_id | BIGINT UNSIGNED | FK → registrations.id, NOT NULL | Inscription concernée |
| name | VARCHAR(255) | NOT NULL | Nom de la remise (ex: "Remise famille", "Bourse") |
| type | ENUM | NOT NULL | "percentage" ou "fixed" | Type de remise |
| value | DECIMAL(10,2) | NOT NULL | Valeur de la remise (pourcentage ou montant fixe) |
| valid_from | DATE | NULL | Date de début de validité |
| valid_until | DATE | NULL | Date de fin de validité |
| applied_by | BIGINT UNSIGNED | FK → users.id, NULL | Utilisateur qui a accordé la remise |
| reason | TEXT | NULL | Raison de la remise |
| created_at | TIMESTAMP | NULL | Date de création |
| updated_at | TIMESTAMP | NULL | Date de mise à jour |

---

### 1.4 Table `invoices` (Factures)
Gère les factures émises pour les élèves.

| Colonne | Type | Contrainte | Description |
|---------|------|------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Identifiant unique |
| registration_id | BIGINT UNSIGNED | FK → registrations.id, NOT NULL | Inscription concernée |
| invoice_number | VARCHAR(255) | UNIQUE, NOT NULL | Numéro unique de facture (ex: FACT-2026-0001) |
| total_amount | DECIMAL(10,2) | NOT NULL | Montant total de la facture (après remises) |
| due_date | DATE | NOT NULL | Date d'échéance |
| status | ENUM | NOT NULL | "draft", "sent", "paid", "partial", "overdue" | Statut de la facture |
| issued_at | TIMESTAMP | NULL | Date d'émission |
| created_at | TIMESTAMP | NULL | Date de création |
| updated_at | TIMESTAMP | NULL | Date de mise à jour |

---

### 1.5 Table `invoice_items` (Lignes de Facture)
Détaille les éléments facturés sur une facture.

| Colonne | Type | Contrainte | Description |
|---------|------|------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Identifiant unique |
| invoice_id | BIGINT UNSIGNED | FK → invoices.id, NOT NULL | Facture concernée |
| fee_type_id | BIGINT UNSIGNED | FK → fee_types.id, NULL | Type de frais |
| description | TEXT | NOT NULL | Description de la ligne |
| quantity | INT UNSIGNED | DEFAULT 1 | Quantité |
| unit_price | DECIMAL(10,2) | NOT NULL | Prix unitaire |
| total | DECIMAL(10,2) | NOT NULL | Total de la ligne |
| created_at | TIMESTAMP | NULL | Date de création |

---

### 1.6 Table `payment_invoice` (Paiements ↔ Factures)
Pivot table pour associer les paiements aux factures (plusieurs paiements pour une facture, plusieurs factures pour un paiement).

| Colonne | Type | Contrainte | Description |
|---------|------|------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Identifiant unique |
| payment_id | BIGINT UNSIGNED | FK → payments.id, NOT NULL | Paiement concerné |
| invoice_id | BIGINT UNSIGNED | FK → invoices.id, NOT NULL | Facture concernée |
| amount_applied | DECIMAL(10,2) | NOT NULL | Montant appliqué à cette facture |

---

### 1.7 Table `credit_notes` (Avoirs)
Gère les avoirs des élèves (surplus de paiement convertis en crédit).

| Colonne | Type | Contrainte | Description |
|---------|------|------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Identifiant unique |
| registration_id | BIGINT UNSIGNED | FK → registrations.id, NOT NULL | Inscription concernée |
| amount | DECIMAL(10,2) | NOT NULL | Montant de l'avoir |
| reason | TEXT | NULL | Raison de l'avoir (ex: "Paiement en trop") |
| used_amount | DECIMAL(10,2) | DEFAULT 0.00 | Montant utilisé |
| remaining_amount | DECIMAL(10,2) | GENERATED ALWAYS AS (amount - used_amount) STORED | Montant restant |
| status | ENUM | NOT NULL | "available", "used", "expired" | Statut de l'avoir |
| created_at | TIMESTAMP | NULL | Date de création |

---

### 1.8 Table `audit_logs` (Journal des Opérations)
Enregistre toutes les actions financières pour l'audit.

| Colonne | Type | Contrainte | Description |
|---------|------|------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Identifiant unique |
| user_id | BIGINT UNSIGNED | FK → users.id, NULL | Utilisateur qui a effectué l'action |
| action | VARCHAR(255) | NOT NULL | Type d'action (create_payment, update_payment, delete_payment, etc.) |
| model_type | VARCHAR(255) | NOT NULL | Type de modèle concerné (Payment, Invoice, etc.) |
| model_id | BIGINT UNSIGNED | NULL | ID du modèle concerné |
| old_values | JSON | NULL | Valeurs avant modification |
| new_values | JSON | NULL | Valeurs après modification |
| ip_address | VARCHAR(45) | NULL | Adresse IP de l'utilisateur |
| user_agent | TEXT | NULL | Navigateur de l'utilisateur |
| comment | TEXT | NULL | Commentaire |
| created_at | TIMESTAMP | NULL | Date de l'action |

---

## 2. Logique Métier Clé

### 2.1 Règle 1 : Paiement Bloquant (Vérification de l'ordre chronologique)
L'objectif : Interdire le paiement d'une facture N+1 tant que toutes les factures précédentes ne sont pas soldées.

```php
<?php
// Exemple de logique métier (à placer dans un Service : app/Services/PaymentService.php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Registration;

class PaymentService
{
    public function canPayInvoice(Invoice $invoice): bool
    {
        $registration = $invoice->registration;
        $previousInvoices = Invoice::where('registration_id', $registration->id)
            ->where('id', '<', $invoice->id)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($previousInvoices as $prevInvoice) {
            if (!in_array($prevInvoice->status, ['paid', 'partial'])) {
                return false;
            }

            if ($prevInvoice->status === 'partial') {
                // Vérifie si la facture partielle a un solde restant
                if ($prevInvoice->remaining_balance > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    // ... Autres méthodes
}
```

---

### 2.2 Règle 2 : Gestion des Avoirs (Surplus de paiement)
Quand un paiement dépasse le montant dû, l'excédent est automatiquement converti en avoir.

```php
<?php
// Dans PaymentService.php

public function handleSurplusAsCredit(Registration $registration, float $surplus): void
{
    if ($surplus <= 0) {
        return;
    }

    CreditNote::create([
        'registration_id' => $registration->id,
        'amount' => $surplus,
        'reason' => 'Surplus de paiement',
        'used_amount' => 0,
        'remaining_amount' => $surplus,
        'status' => 'available',
    ]);
}
```

---

## 3. Architecture des Controllers

### 3.1 Structure du `PaymentController`
Structure clean, avec séparation des responsabilités.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
        // Injection de dépendance du service métier
    }

    /**
     * Étape 1 : Valider la demande de paiement
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $registration = $invoice->registration;

        // Vérifier la règle de paiement bloquant
        if (!$this->paymentService->canPayInvoice($invoice)) {
            return back()->withErrors(['invoice' => 'Impossible de payer cette facture : les factures précédentes ne sont pas soldées.']);
        }

        // Vérifier si le paiement partiel
        $isPartial = $validated['amount'] < $invoice->total_amount;
        if ($isPartial && Gate::denies('validate-partial-payment')) {
            abort(403);
        }

        // ...
        // Étape 2 : Enregistrer le paiement (dans une transaction)
        DB::transaction(function () use ($validated, $invoice, $isPartial) {
            $payment = Payment::create([
                'registration_id' => $invoice->registration_id,
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'payment_date' => now(),
                'payment_method' => $validated['payment_method'],
                'status' => $isPartial ? 'partial' : 'completed',
                // ... Autres champs
            ]);

            // Étape 3 : Appliquer le paiement à la facture
            $this->paymentService->applyPaymentToInvoice($payment, $invoice);

            // Étape 4 : Gérer les avoirs si surplus
            $surplus = $validated['amount'] - $invoice->total_amount;
            $this->paymentService->handleSurplusAsCredit($invoice->registration, $surplus);
        });

        return back()->with('success', 'Paiement enregistré avec succès.');
    }

    // ... Autres méthodes (index, show, etc.)
}
```

---

## 4. Liste des Modèles à Créer/Mettre à jour

1. **Nouveaux modèles :
   - `FeeType` (app/Models/FeeType.php)
   - `ClassroomFee` (app/Models/ClassroomFee.php)
   - `Discount` (app/Models/Discount.php)
   - `Invoice` (app/Models/Invoice.php)
   - `InvoiceItem` (app/Models/InvoiceItem.php)
   - `CreditNote` (app/Models/CreditNote.php)
   - `AuditLog` (app/Models/AuditLog.php)

2. **Modèles à mettre à jour :
   - `Payment` : Ajouter relation avec `Invoice`
   - `Registration` : Ajouter relations avec `FeeType`, `Discount`, `Invoice`, `CreditNote`
