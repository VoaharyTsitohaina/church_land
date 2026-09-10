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
use Livewire\Attributes\On;
use App\Exports\PropertiesExport;

class ByDistrictReportWidget extends BaseWidget {
    
    use ScopesPropertiesByUser;
    
    protected static ?string $heading = 'Patrimoine par District';

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
                ->selectRaw('districts.id as id, districts.name as label, count(properties.id) as total')
                ->groupBy('districts.id', 'districts.name')
        );
    }

    protected function detailedQuery(): Builder
    {
        return $this->scopeQueryWithFilter(
            Property::query()
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->join('districts', 'churches.district_id', '=', 'districts.id')
                ->select('properties.*')
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
                TextColumn::make('label')->label('District'),
                TextColumn::make('total')->label('Nombre de bien')->sortable(),
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
                    'patrimoine-par-district-detaille.xlsx'
                )),
            ]);
    }
}