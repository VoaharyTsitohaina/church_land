<?php

namespace App\Exports;

use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PropertiesExport implements FromCollection, WithHeadings, WithMapping
{
    protected Builder $query;

    // On accepte soit rien (toutes les propriétés), soit une requête déjà filtrée
    public function __construct(?Builder $query = null)
    {
        $this->query = $query ?? Property::query();
        $this->query->with(['church.district.federation', 'type']);
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'Référence', 'Nom', 'Type', 'Église', 'District', 'Fédération',
            'Superficie', 'Titre foncier', 'Statut juridique', 'Valeur estimée',
        ];
    }

    public function map($property): array
    {
        return [
            $property->reference,
            $property->name,
            $property->type?->name,
            $property->church?->name,
            $property->church?->district?->name,
            $property->church?->district?->federation?->name,
            $property->area,
            $property->land_title_number ?? '—',
            $property->legal_status,
            $property->estimated_value,
        ];
    }
}