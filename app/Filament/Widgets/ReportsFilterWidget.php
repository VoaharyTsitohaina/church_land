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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Pré-remplit selon le périmètre fixe du rôle (comme avant dans Reports.php)
        if ($user->hasRole('district_manager')) {
            $this->district_id = $user->district_id;
        } elseif ($user->hasRole('federation_admin')) {
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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $form->schema([
            Select::make('federation_id')
                ->label('Fédération/Mission')
                ->options(Federation::pluck('name', 'id'))
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
                ->visible(fn () => !$user->hasRole(['district_manager', 'federation_admin'])),

            Select::make('district_id')
                ->label('District')
                ->options(fn ($get) => District::when(
                    $get('federation_id'),
                    fn ($q) => $q->where('federation_id', $get('federation_id'))
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
                ->visible(fn () => !$user->hasRole('district_manager')),

            Select::make('church_id')
                ->label('Église')
                ->options(function ($get) {
    $districtId = $get('district_id');
    $federationId = $get('federation_id');

    return Church::query()
        ->when(
            $districtId,
            fn ($q) => $q->where('district_id', $districtId)
        )
        ->when(
            !$districtId && $federationId,
            fn ($q) => $q->whereHas('district', fn ($q2) => $q2->where('federation_id', $federationId))
        )
        ->pluck('name', 'id');
})
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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->hasRole(['district_manager', 'federation_admin'])) {
            $this->federation_id = null;
            $this->district_id = null;
            $this->church_id = null;
            $this->form->fill();
        } elseif (!$user->hasRole('district_manager')) {
            $this->district_id = null;
            $this->church_id = null;
            $this->form->fill(['federation_id' => $this->federation_id]);
        } else {
            $this->church_id = null;
            $this->form->fill(['district_id' => $this->district_id]);
        }

        $this->persistFilter();
    }
}