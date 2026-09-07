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
use App\Exports\PropertiesExport;
use Livewire\Attributes\On;

class WithoutTitleReportWidget extends BaseWidget {

    use ScopesPropertiesByUser;

    protected static ?string $heading = 'Biens sans titre foncier';

    #[On('reports-filter-updated')]
    public function refresh(): void
    {
        // vide : force Livewire à relire la session au re-render
    }

    protected function reportQuery(): Builder
    {
        return $this->scopeQueryWithFilter(
            Property::query()
                ->whereNull('land_title_number')
                ->with('church')
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->reportQuery())
            ->columns([
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('church.name')
                    ->label('Église')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Enregistré le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->headerActions([
                Action::make('export')
                    ->label('Exporter')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $rows = $this->reportQuery()->get()->map(fn ($r) => [$r->total])->toArray();
                        return Excel::download(
                            new PropertiesExport($this->reportQuery()),
                        'biens-sans-titre.xlsx'
                        );
                    }),
            ]);
    }
}