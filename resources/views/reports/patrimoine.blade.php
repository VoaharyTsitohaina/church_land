<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Rapport du patrimoine foncier</h2>
    <p>Généré le {{ now()->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Référence</th><th>Nom</th><th>Type</th><th>Église</th>
                <th>District</th><th>Fédération</th><th>Superficie</th><th>Titre foncier</th>
            </tr>
        </thead>
        <tbody>
            @foreach($properties as $property)
            <tr>
                <td>{{ $property->reference }}</td>
                <td>{{ $property->name }}</td>
                <td>{{ $property->type?->name }}</td>
                <td>{{ $property->church?->name }}</td>
                <td>{{ $property->church?->district?->name }}</td>
                <td>{{ $property->church?->district?->federation?->name }}</td>
                <td>{{ $property->area }}</td>
                <td>{{ $property->land_title_number ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>