<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture - {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .invoice {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .invoice-number {
            background: #f0f0f0;
            padding: 10px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-section h3 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
            color: #333;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .info-row label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 150px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #f0f0f0;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #333;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .total-section {
            text-align: right;
            margin: 30px 0;
        }
        .total {
            font-size: 24px;
            font-weight: bold;
            color: #2e7d32;
            padding: 20px;
            background: #f9f9f9;
            border: 2px solid #2e7d32;
            display: inline-block;
            min-width: 300px;
        }
        .status {
            text-align: center;
            padding: 10px;
            margin-bottom: 30px;
            font-weight: bold;
            border-radius: 5px;
        }
        .status.paid {
            background: #d4edda;
            color: #155724;
        }
        .status.pending {
            background: #fff3cd;
            color: #856404;
        }
        .status.overdue {
            background: #f8d7da;
            color: #721c24;
        }
        .status.cancelled {
            background: #e2e3e5;
            color: #383d41;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="header">
            <h1>EduManager - Facture</h1>
            <p>Système de Gestion Éducative</p>
        </div>

        <div class="invoice-number">
            Facture N° {{ $invoice->invoice_number }}
        </div>

        <div class="status {{ $invoice->status }}">
            Statut: {{ ucfirst($invoice->status) }}
        </div>

        <div class="info-grid">
            <div class="info-section">
                <h3>Informations de la Facture</h3>
                <div class="info-row">
                    <label>Date d'émission:</label>
                    <span>{{ $invoice->issued_at->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <label>Date d'échéance:</label>
                    <span>{{ $invoice->due_date->format('d/m/Y') }}</span>
                </div>
            </div>

            <div class="info-section">
                <h3>Informations de l'Élève</h3>
                <div class="info-row">
                    <label>Nom:</label>
                    <span>{{ $invoice->registration->user->name }}</span>
                </div>
                <div class="info-row">
                    <label>Classe:</label>
                    <span>{{ $invoice->registration->classroom?->name ?? 'Non assigné' }}</span>
                </div>
            </div>
        </div>

        <h3 style="margin-bottom: 15px; color: #333;">Détails de la Facture</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->feeType?->name ?? '-' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                        <td>{{ number_format($item->total, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold;">Total:</td>
                    <td style="font-weight: bold;">{{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA</td>
                </tr>
            </tfoot>
        </table>

        <div class="total-section">
            <div class="total">
                Total: {{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA
            </div>
        </div>

        @if($invoice->remaining_balance > 0)
            <div class="info-section">
                <h3>État du Paiement</h3>
                <div class="info-row">
                    <label>Payé:</label>
                    <span style="color: #2e7d32; font-weight: bold;">{{ number_format($invoice->total_amount - $invoice->remaining_balance, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="info-row">
                    <label>Reste à payer:</label>
                    <span style="color: #c62828; font-weight: bold;">{{ number_format($invoice->remaining_balance, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        @endif

        <div class="footer">
            <p>Facture générée automatiquement par EduManager</p>
            <p>Date d'émission: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
