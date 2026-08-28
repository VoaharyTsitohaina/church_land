<?php

namespace App\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use App\Models\Property;
use Illuminate\Support\Facades\DB;
use App\Filament\Concerns\ScopesPropertiesByUser;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class ByFederationReportWidget extends BaseWidget{
    
    use ScopesPropertiesByUser;

    protected static ?string $heading = 'Patrimoine par Fédération/Mission';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->scopeQuery(
                    Property::query()
                        ->join('churches', 'properties.church_id', '=', 'churches.id')
                        ->join('districts', 'churches.district_id', '=', 'districts.id')
                        ->join('federations', 'districts.federation_id', '=', 'federations.id')
                        ->selectRaw('federations.id as id, federations.name as label, count(properties.id) as total')
                        ->groupBy('federations.id', 'federations.name')
                )
            )
            ->columns([
                TextColumn::make('label')
                    ->label('Federation'),
                TextColumn::make('total')
                    ->label('Nombre de bien')
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
                    $rows = $this->getTableQuery()->get()->map(fn ($r) => [$r->label, $r->total])->toArray();
                    return \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\ArrayExport($rows, ['Fédération/Mission', 'Total de biens']),
                        'patrimoine-par-federation.xlsx'
                    );
                }),
        ]);
    }
}

