<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use \Filament\Tables\Table;
use \Filament\Tables\Columns\TextColumn;
use \Filament\Tables\Actions\Action;
use \Maatwebsite\Excel\Facades\Excel;
use \App\Exports\ArrayExport;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Filament\Concerns\ScopesPropertiesByUser;
use Livewire\Attributes\On;
use App\Exports\PropertiesExport;

class ByChurchReportWidget extends BaseWidget
{
    use ScopesPropertiesByUser;

    #[On('reports-filter-updated')]
    public function refresh(): void
    {
        $this->resetTable();
    }

    protected static ?string $heading = 'Patrimoine par Eglise';

    protected function reportQuery(): Builder
    {
        return $this->scopeQueryWithFilter(
            Property::query()
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->selectRaw('churches.id as id, churches.name as label, count(properties.id) as total')
                ->groupBy('churches.id', 'churches.name')
        );
    }

    protected function detailedQuery(): Builder
    {
        return $this->scopeQueryWithFilter(
            Property::query()
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->select('properties.*')
                ->orderBy('churches.name')
                ->orderBy('properties.name')
        )->with(['church.district.federation', 'type']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->reportQuery())
            ->columns([
                TextColumn::make('label')->label('Eglise'),
                TextColumn::make('total')->label('Nombre de bien')->sortable(),
            ])
            ->defaultSort('total', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->headerActions([

                Action::make('exportDetailed')
                    ->label('Exporter Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => Excel::download(
                        new PropertiesExport($this->detailedQuery()),
                        'patrimoine-par-eglise-detaille.xlsx'
                    )),

                Action::make('exportPdf')
                    ->label('Exporter PDF')
                    ->icon('heroicon-o-printer')
                    ->action(fn () => $this->downloadPropertiesPdf(
                        $this->detailedQuery(),
                        'Patrimoine par Eglise',
                        'patrimoine-par-eglise-detaille.pdf'
                    ))
            ]);
    }
}