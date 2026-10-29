<?php

namespace App\Filament\Widgets;

use App\Models\Church;
use App\Models\District;
use App\Models\Federation;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Support\Perimeter;

class ReportsFilterWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.reports-filter';
    protected static ?int $sort = 0; // toujours en premier
    protected int|string|array $columnSpan = 'full';

    public ?int $federation_id = null;
    public ?int $district_id = null;
    public ?int $church_id = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (Perimeter::type($user) === 'district') {
            $this->district_id = $user->district_id;
        } elseif (Perimeter::type($user) === 'federation') {
            $this->federation_id = $user->federation_id;
        }

        $this->form->fill([
            'federation_id' => $this->federation_id,
            'district_id' => $this->district_id,
            'church_id' => $this->church_id,
        ]);

        $this->persistFilter();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('federation_id')
                ->label('Fédération/Mission')
                ->options(fn () => Perimeter::federations(Federation::query())->pluck('name', 'id'))
                ->placeholder('Toutes')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($set) {
                    $set('district_id', null);
                    $set('church_id', null);
                    $this->district_id = null;
                    $this->church_id = null;
                    $this->persistFilter();
                })
                ->visible(fn () => Perimeter::type() === 'all'),

            Select::make('district_id')
                ->label('District')
                ->options(fn ($get) => Perimeter::districts(
                    District::query()->when(
                        $get('federation_id'),
                        fn ($q) => $q->where('federation_id', $get('federation_id'))
                    )
                )->pluck('name', 'id'))
                ->placeholder('Tous')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($set) {
                    $set('church_id', null);
                    $this->church_id = null;
                    $this->persistFilter();
                })
                ->visible(fn () => Perimeter::type() !== 'district'),

            Select::make('church_id')
                ->label('Église')
                ->options(fn ($get) => Perimeter::churches(
                    Church::query()
                        ->when($get('district_id'), fn ($q) => $q->where('district_id', $get('district_id')))
                        ->when(
                            ! $get('district_id') && $get('federation_id'),
                            fn ($q) => $q->whereHas('district', fn ($d) => $d->where('federation_id', $get('federation_id')))
                        )
                )->pluck('name', 'id'))
                ->placeholder('Toutes')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn () => $this->persistFilter()),
        ])->columns(3);
    }

    // Écrit le filtre courant en session et prévient tous les widgets de rapports de se rafraîchir
    protected function persistFilter(): void
    {
        session([
            'reports_filter.federation_id' => $this->federation_id,
            'reports_filter.district_id' => $this->district_id,
            'reports_filter.church_id' => $this->church_id,
        ]);

        $this->dispatch('reports-filter-updated');
    }

    public function resetFilter(): void
    {
        $user = Auth::user();
        $type = Perimeter::type($user);

        $this->federation_id = $type === 'federation' ? $user->federation_id : null;
        $this->district_id = $type === 'district' ? $user->district_id : null;
        $this->church_id = null;

        $this->form->fill([
            'federation_id' => $this->federation_id,
            'district_id' => $this->district_id,
            'church_id' => null,
        ]);

        $this->persistFilter();
    }
}