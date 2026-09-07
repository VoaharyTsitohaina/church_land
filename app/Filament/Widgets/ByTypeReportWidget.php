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

class ByTypeReportWidget extends BaseWidget {

    use ScopesPropertiesByUser;

    #[On('reports-filter-updated')]
    public function refresh(): void
    {
        // vide : force Livewire à relire la session au re-render
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
            Action::make('export')
                ->label('Exporter')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $rows = $this->reportQuery()->get()->map(fn ($r) => [$r->label, $r->total])->toArray();
                    return Excel::download(
                        new ArrayExport($rows, ['Type de bien', 'Total de biens']),
                        'patrimoine-par-type.xlsx'
                    );
                }),
            ]);
    }
}