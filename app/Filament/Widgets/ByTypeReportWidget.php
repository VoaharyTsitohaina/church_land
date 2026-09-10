<?php

namespace App\Filament\Widgets;

use App\Exports\ArrayExport;
use App\Filament\Concerns\ScopesPropertiesByUser;
use App\Models\Property;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Attributes\On;
use App\Exports\PropertiesExport;

class ByTypeReportWidget extends BaseWidget {

    use ScopesPropertiesByUser;

    #[On('reports-filter-updated')]
    public function refresh(): void
    {
        $this->resetTable();
    }

    protected static ?string $heading = 'Répartition par type de bien';

    protected function reportQuery(): Builder
    {
        return $this->scopeQueryWithFilter(
            Property::query()
                ->join('property_types', 'properties.property_type_id', '=', 'property_types.id')
                ->selectRaw('property_types.id as id, property_types.name as label, count(properties.id) as total')
                ->groupBy('property_types.id', 'property_types.name')
        );
    }

    protected function detailedQuery(): Builder
    {
        return $this->scopeQueryWithFilter(
            Property::query()
                ->join('property_types', 'properties.property_type_id', '=', 'property_types.id')
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->join('districts', 'churches.district_id', '=', 'districts.id')
                ->join('federations', 'districts.federation_id', '=', 'federations.id')
                ->select('properties.*')
                ->orderBy('property_types.name')
                ->orderBy('federations.name')
                ->orderBy('districts.name')
                ->orderBy('churches.name')
                ->orderBy('properties.name')
        )->with(['church.district.federation', 'type']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->reportQuery())
            ->columns([
                TextColumn::make('label')
                    ->label('Type de bien'),

                TextColumn::make('total')
                    ->label('Nombre de biens')
                    ->sortable(),
            ])
            ->defaultSort('total', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->headerActions([

            Action::make('exportDetailed')
                    ->label('Exporter la liste détaillée')
                    ->icon('heroicon-o-document-text')
                    ->action(fn () => Excel::download(
                        new PropertiesExport($this->detailedQuery()),
                        'patrimoine-par-type-detaille.xlsx'
                    )),
            ]);
    }
}