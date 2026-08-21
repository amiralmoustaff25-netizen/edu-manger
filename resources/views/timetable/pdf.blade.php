<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps {{ $classroom->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h1 { font-size: 16px; margin: 0; text-transform: uppercase; }
        .header h2 { font-size: 12px; margin: 3px 0; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; text-align: center; }
        th { background-color: #333; color: white; font-weight: bold; }
        td.slot { background-color: #f5f5f5; font-weight: bold; text-align: left; white-space: nowrap; }
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc; text-align: center; font-size: 9px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>École — Emploi du temps</h1>
        <h2>{{ $classroom->name }} | {{ $classroom->schoolYear?->year_string }}</h2>
        @if($classroom->teacher)
            <h2>Professeur principal : {{ $classroom->teacher->name }}</h2>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Horaire</th>
                @foreach($days as $day)
                    <th>{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($slots as $slot)
                <tr>
                    <td class="slot">{{ $slot }}</td>
                    @foreach($days as $day)
                        <td>{{ $entries[$day][$slot] ?: '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
