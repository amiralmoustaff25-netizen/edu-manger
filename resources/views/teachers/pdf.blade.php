<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des professeurs</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 20px; }
        h1 { font-size: 18px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f5f5f5; text-align: left; }
        .text-right { text-align: right; }
        .small { font-size: 11px; color: #555; }
    </style>
</head>
<body>
    <h1>Liste des professeurs</h1>
    <p class="small">Établissement : {{ $ecole }}<br>Imprimé le {{ $date }}</p>

    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Statut</th>
                <th>Ancienneté</th>
                <th>Heures/semaine</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teachers as $teacher)
                <tr>
                    <td>{{ $teacher->matricule }}</td>
                    <td>{{ $teacher->user?->name ?? '—' }}</td>
                    <td>{{ $teacher->user?->email ?? '—' }}</td>
                    <td>{{ ucfirst($teacher->statut) }}</td>
                    <td>{{ $teacher->anciennete() }}</td>
                    <td class="text-right">{{ $teacher->volume_horaire_actuel }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
