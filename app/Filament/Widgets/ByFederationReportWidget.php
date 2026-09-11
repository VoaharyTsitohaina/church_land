<?php

namespace App\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\ScopesPropertiesByUser;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;
use App\Exports\PropertiesExport;
use Livewire\Attributes\On;

class ByFederationReportWidget extends BaseWidget{
    
    use ScopesPropertiesByUser;

    protected static ?string $heading = 'Patrimoine par Fédération/Mission';

    #[On('reports-filter-updated')]
    public function refresh(): void
    {
        $this->resetTable();
    }

    protected function reportQuery(): Builder
    {
        return $this->scopeQueryWithFilter(
            Property::query()
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->join('districts', 'churches.district_id', '=', 'districts.id')
                ->join('federations', 'districts.federation_id', '=', 'federations.id')
                ->selectRaw('federations.id as id, federations.name as label, count(properties.id) as total')
                ->groupBy('federations.id', 'federations.name')
        );
    }

    protected function detailedQuery(): Builder
    {
        return $this->scopeQueryWithFilter(
            Property::query()
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->join('districts', 'churches.district_id', '=', 'districts.id')
                ->join('federations', 'districts.federation_id', '=', 'federations.id')
                ->select('properties.*')
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
                TextColumn::make('label')->label('Federation'),
                TextColumn::make('total')->label('Nombre de bien')->sortable(),
            ])
            ->defaultSort('total', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->headerActions([
            Action::make('exportDetailed')
                    ->label('Exporter')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => Excel::download(
                        new PropertiesExport($this->detailedQuery()),
                        'patrimoine-par-federation-detaille.xlsx'
                    )),
            ]);
    }
}

