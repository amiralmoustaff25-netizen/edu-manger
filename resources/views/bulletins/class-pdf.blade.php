<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletins Classe {{ $classroom->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
        }
        .page-break {
            page-break-after: always;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 12px;
            margin: 3px 0;
            font-weight: normal;
        }
        .student-info {
            background-color: #f5f5f5;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .student-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .student-info td {
            padding: 4px;
            border: none;
        }
        .student-info .label {
            font-weight: bold;
            width: 120px;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #333;
            padding: 4px;
            text-align: center;
        }
        .grades-table th {
            background-color: #333;
            color: white;
            font-weight: bold;
        }
        .grades-table .subject {
            text-align: left;
        }
        .summary {
            background-color: #f9f9f9;
            padding: 10px;
            margin-top: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary td {
            padding: 5px;
            border: none;
        }
        .summary .label {
            font-weight: bold;
        }
        .summary .value {
            font-size: 14px;
            font-weight: bold;
            color: #0066cc;
        }
        .mention {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            margin-top: 8px;
            border-radius: 4px;
        }
        .mention.excellent { background-color: #d4edda; color: #155724; }
        .mention.tres-bien { background-color: #d1ecf1; color: #0c5460; }
        .mention.bien { background-color: #fff3cd; color: #856404; }
        .mention.assez-bien { background-color: #e2e3e5; color: #383d41; }
        .mention.passable { background-color: #f8d7da; color: #721c24; }
        .mention.insuffisant { background-color: #f5c6cb; color: #721c24; }
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .signature {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 150px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    @php
        // Système "sunuBulletin" (primaire) : chaque matière a son propre barème (note
        // max) plutôt qu'un coefficient appliqué à une note /20 — même classe pour tous
        // les élèves de ce PDF groupé, calculé une seule fois.
        $usesBaremeSystem = app(\App\Services\GradeCalculationService::class)->usesBaremeSystem($classroom, $classroom->school_year_id);
    @endphp
    @foreach($bulletins as $index => $bulletin)
    @if($index > 0)
    <div class="page-break"></div>
    @endif
    
    <div class="header">
        <h1>École - Bulletin Scolaire</h1>
        <h2>Classe {{ $classroom->name }} | Année Scolaire {{ $bulletin['classroom']->schoolYear->year_string ?? 'N/A' }}</h2>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td class="label">Nom & Prénom :</td>
                <td>{{ $bulletin['student']->name }}</td>
                <td class="label">Matricule :</td>
                <td>{{ $bulletin['student']->matricule }}</td>
            </tr>
            <tr>
                <td class="label">Période :</td>
                <td>{{ ucfirst(str_replace('_', ' ', $period)) }}</td>
                <td class="label">Classement :</td>
                <td>{{ $bulletin['rank'] }}<sup>ème</sup></td>
            </tr>
        </table>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th class="subject">Matière</th>
                <th>{{ $usesBaremeSystem ? 'Barème' : 'Coef' }}</th>
                <th>Notes</th>
                <th>{{ $usesBaremeSystem ? 'Points obtenus' : 'Moyenne' }}</th>
                @unless($usesBaremeSystem)
                    <th>Moy. × Coef</th>
                @endunless
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bulletin['subjects'] as $subject)
            <tr>
                <td class="subject">{{ $subject['matiere'] }}</td>
                <td>{{ $subject['coefficient'] }}</td>
                <td>{{ implode(', ', $subject['notes']) ?: '-' }}</td>
                <td>{{ $subject['average'] > 0 ? number_format($subject['average'], 2) : '-' }}</td>
                @unless($usesBaremeSystem)
                    <td>{{ $subject['weighted_average'] > 0 ? number_format($subject['weighted_average'], 2) : '-' }}</td>
                @endunless
                <td>{{ $subject['appreciation'] ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right; font-weight: bold;">{{ $usesBaremeSystem ? 'Total Barèmes :' : 'Total Coefficients :' }}</td>
                <td colspan="{{ $usesBaremeSystem ? 3 : 4 }}" style="font-weight: bold;">{{ $bulletin['total_coefficients'] }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td class="label">Moyenne Générale :</td>
                <td class="value">{{ number_format($bulletin['general_average'], 2) }}/20</td>
            </tr>
        </table>
        
        @php
            $mentionClass = match($bulletin['mention']) {
                'Excellent' => 'excellent',
                'Très Bien' => 'tres-bien',
                'Bien' => 'bien',
                'Assez Bien' => 'assez-bien',
                'Passable' => 'passable',
                default => 'insuffisant',
            };
        @endphp
        
        <div class="mention {{ $mentionClass }}">
            {{ $bulletin['mention'] }}
        </div>
    </div>

    <div class="signature">
        <div class="signature-box">
            <div>Le Chef d'Établissement</div>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <div>Le Professeur Principal</div>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <div>Les Parents</div>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        <p>Bulletin généré le {{ now()->format('d/m/Y H:i') }} | Document officiel</p>
    </div>
    @endforeach
</body>
</html>
