<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reçu de Paiement - {{ $payment->receipt_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .receipt {
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
        .receipt-number {
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
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #2e7d32;
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border: 2px solid #2e7d32;
            margin: 30px 0;
        }
        .status {
            text-align: center;
            padding: 10px;
            margin-bottom: 30px;
            font-weight: bold;
            border-radius: 5px;
        }
        .status.complete {
            background: #d4edda;
            color: #155724;
        }
        .status.partial {
            background: #fff3cd;
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .fee-table th, .fee-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .fee-table th {
            background: #f0f0f0;
        }
        .fee-table td:last-child {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h1>EduManager - Reçu de Paiement</h1>
            <p>Système de Gestion Éducative</p>
        </div>

        <div class="receipt-number">
            Reçu N° {{ $payment->receipt_number }}
        </div>

        <div class="status {{ $payment->status }}">
            Statut: {{ ucfirst($payment->status) }}
        </div>

        <div class="info-grid">
            <div class="info-section">
                <h3>Informations du Paiement</h3>
                <div class="info-row">
                    <label>Date:</label>
                    <span>{{ $payment->payment_date->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <label>Mois:</label>
                    <span>{{ $payment->month }}</span>
                </div>
                <div class="info-row">
                    <label>Méthode:</label>
                    <span>{{ ucfirst($payment->payment_method) }}</span>
                </div>
                <div class="info-row">
                    <label>Type:</label>
                    <span>{{ ucfirst($payment->payment_type) }}</span>
                </div>
            </div>

            <div class="info-section">
                <h3>Informations de l'Élève</h3>
                <div class="info-row">
                    <label>Nom:</label>
                    <span>{{ $payment->registration->user->name }}</span>
                </div>
                <div class="info-row">
                    <label>Classe:</label>
                    <span>{{ $payment->registration->classroom?->name ?? 'Non assigné' }}</span>
                </div>
                <div class="info-row">
                    <label>Année:</label>
                    <span>{{ $payment->registration->schoolYear?->year_string ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="amount">
            Montant Payé: {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
        </div>

        @if($payment->fee_breakdown && count($payment->fee_breakdown))
            <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">Détail des frais</h3>
            <table class="fee-table">
                <thead>
                    <tr>
                        <th>Frais</th>
                        <th>Montant dû</th>
                        <th>Montant payé</th>
                        <th>Reste</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payment->fee_breakdown as $fee)
                        <tr>
                            <td>{{ $fee['description'] ?? '-' }}</td>
                            <td>{{ number_format(($fee['amount'] ?? 0) + ($fee['amount_paid'] ?? 0), 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format($fee['amount_paid'] ?? 0, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format($fee['remaining_balance'] ?? 0, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($payment->status === 'partiel')
            <div class="info-section" style="margin-top: 20px;">
                <h3>Reste à Payer</h3>
                <div class="info-row">
                    <label>Reste:</label>
                    <span style="color: #c62828; font-weight: bold;">{{ number_format($payment->remaining_balance, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        @endif

        @if($payment->comment)
            <div class="info-section" style="margin-top: 20px;">
                <h3>Commentaire</h3>
                <p>{{ $payment->comment }}</p>
            </div>
        @endif

        @if($payment->validatedBy)
            <div class="info-section" style="margin-top: 20px;">
                <h3>Validation</h3>
                <div class="info-row">
                    <label>Validé par:</label>
                    <span>{{ $payment->validatedBy->name }}</span>
                </div>
            </div>
        @endif

        <div class="footer">
            <p>Reçu généré automatiquement par EduManager</p>
            <p>Date d'émission: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
