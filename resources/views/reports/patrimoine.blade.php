
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #222;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            width: auto;
            height: 55px;
            margin-bottom: 8px;
        }

        h2 {
            margin: 0;
            font-size: 18px;
        }

        .date {
            margin-top: 5px;
            color: #666;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            font-weight: bold;
        }

        td {
            vertical-align: top;
        }
    </style>
</head>

<body>

    @php
        $logoPath = public_path('sgfeNoBackground.png');
        $logoPathAdventist = public_path('adventist-circle.png');
    @endphp

    <div class="header">
        
        <img src="{{ $logoPath }}" alt="SGFE" class="logo">

        <img src="{{ $logoPathAdventist }}" alt="Adventist" class="logo">

        <h2>Rapport du patrimoine foncier</h2>

        @if(!empty($scopeLabel))
            <div class="scope">{{ $scopeLabel }}</div>
        @endif
        
        <div class="date">
            Généré le {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Église</th>
                <th>District</th>
                <th>Fédération</th>
                <th>Superficie</th>
                <th>Titre foncier</th>
            </tr>
        </thead>

        <tbody>
            @foreach($properties as $property)
                <tr>
                    <td>{{ $property->reference }}</td>

                    <td>{{ $property->name }}</td>

                    <td>{{ $property->type?->name ?? '—' }}</td>

                    <td>{{ $property->church?->name ?? '—' }}</td>

                    <td>{{ $property->church?->district?->name ?? '—' }}</td>

                    <td>{{ $property->church?->district?->federation?->name ?? '—' }}</td>

                    <td>
                        {{ $property->area !== null
                            ? number_format($property->area, 2, ',', ' ') . ' m²'
                            : '—'
                        }}
                    </td>

                    <td>{{ $property->land_title_number ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
