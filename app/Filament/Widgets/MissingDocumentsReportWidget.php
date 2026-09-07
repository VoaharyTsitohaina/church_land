<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Filament\Concerns\ScopesPropertiesByUser;
use App\Exports\PropertiesExport;
use Livewire\Attributes\On;

class MissingDocumentsReportWidget extends BaseWidget
{
    use ScopesPropertiesByUser;

    // protected static ?string $heading = 'Biens avec documents manquants';

    #[On('reports-filter-updated')]
    public function refresh(): void
    {
        // vide : force Livewire à relire la session au re-render
    }

    protected function reportQuery(): Builder
    {
        return $this->scopeQueryWithFilter(
            Property::query()
                ->where(function ($query) {
                    $query->whereDoesntHave('media');
                })
                ->with('church')
        );
    }
    

    public function table(Table $table): Table
    {
        return $table
            ->query($this->reportQuery())
            ->heading(fn () => new HtmlString(
                '<div class="flex items-center gap-2">
                    <span>Biens avec documents manquants</span>
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
                            'biens-avec-documents-manquants.xlsx'
                        ); 
                    }),
            ]);
    }
}