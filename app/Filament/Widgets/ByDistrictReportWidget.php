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

class ByDistrictReportWidget extends BaseWidget {
    
    use ScopesPropertiesByUser;
    
    protected static ?string $heading = 'Patrimoine par District';

    protected function reportQuery(): Builder
    {
        return $this->scopeQuery(
            Property::query()
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->join('districts', 'churches.district_id', '=', 'districts.id')
                ->selectRaw('districts.id as id, districts.name as label, count(properties.id) as total')
                ->groupBy('districts.id', 'districts.name')
        );
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
            Action::make('export')
                ->label('Exporter')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $rows = $this->reportQuery()->get()->map(fn ($r) => [$r->label, $r->total])->toArray();
                    return Excel::download(
                        new ArrayExport($rows, ['District', 'Total de biens']),
                        'patrimoine-par-district.xlsx'
                    );
                }),
            ]);
    }
}