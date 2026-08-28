<?php

namespace App\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\Property;
use App\Filament\Concerns\ScopesPropertiesByUser;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\TableWidget as BaseWidget; // <-- Ajout de l'import

class ByTypeReportWidget extends BaseWidget {

    use ScopesPropertiesByUser;

    protected static ?string $heading = 'Répartition par type de bien';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->scopeQuery(
                    Property::query()
                        ->join('property_types', 'properties.property_type_id', '=', 'property_types.id')
                        ->selectRaw('property_types.id as id, property_types.name as label, count(properties.id) as total')
                        ->groupBy('property_types.id', 'property_types.name')
                )
            )
            ->columns([
                TextColumn::make('label')
                    ->label('Type de bien')
                    ->searchable(),

                TextColumn::make('total')
                    ->label('Nombre de biens')
                    ->sortable(),
            ])
            ->defaultSort('total', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}