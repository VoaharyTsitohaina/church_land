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
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PropertiesExport;
use Livewire\Attributes\On;

class WithoutTitleReportWidget extends BaseWidget {

    use ScopesPropertiesByUser;

    #[On('reports-filter-updated')]
    public function refresh(): void
    {
        $this->resetTable();
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
            ->heading(fn () => new HtmlString(
                '<div class="flex items-center gap-2">
                    <span>Biens sans titre foncier</span>
                    <x-filament::badge color="danger">
                        ' . $this->reportQuery()->count() . '
                    </x-filament::badge>
                </div>'
            ))
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
                        return Excel::download(
                            new PropertiesExport($this->reportQuery()),
                        'biens-sans-titre.xlsx'
                        );
                    }),
            ]);
    }
}