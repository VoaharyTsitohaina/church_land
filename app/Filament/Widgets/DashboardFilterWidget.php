<?php

namespace App\Filament\Widgets;

use App\Models\Church;
use App\Models\District;
use App\Models\Federation;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class DashboardFilterWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.dashboard-filter';
    protected static ?int $sort = 0;
    protected int|string|array $columnSpan = 'full';

    public ?int $federation_id = null;
    public ?int $district_id = null;
    public ?int $church_id = null;

    // Seul un utilisateur sans périmètre fixé par son rôle peut filtrer manuellement
    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return ! $user->hasAnyRole(['district_manager','federation_admin']);
    }

    public function mount(): void
    {
        $this->federation_id = session('dashboard_filter.federation_id');
        $this->district_id = session('dashboard_filter.district_id');
        $this->church_id = session('dashboard_filter.church_id');

        $this->form->fill([
            'federation_id' => $this->federation_id,
            'district_id' => $this->district_id,
            'church_id' => $this->church_id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('federation_id')
                ->label('Fédération/Mission')
                ->options(Federation::pluck('name', 'id'))
                ->placeholder('Toutes')
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->district_id = null;
                    $this->church_id = null;
                    $this->applyFilter('federation_id', $state);
                }),

            Select::make('district_id')
                ->label('District')
                ->options(fn ($get) => District::when(
                    $get('federation_id'),
                    fn ($q) => $q->where('federation_id', $get('federation_id'))
                )->pluck('name', 'id'))
                ->placeholder('Tous')
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->church_id = null;
                    $this->applyFilter('district_id', $state);
                }),

            Select::make('church_id')
                ->label('Église')
                ->options(fn ($get) => Church::when(
                    $get('district_id'),
                    fn ($q) => $q->where('district_id', $get('district_id'))
                )->pluck('name', 'id'))
                ->placeholder('Toutes')
                ->searchable()
                ->live()
                ->afterStateUpdated(fn ($state) => $this->applyFilter('church_id', $state)),
        ])->columns(3);
    }

    protected function applyFilter(string $key, int|string|null $value): void
    {
        $this->$key = $value;

        session([
            'dashboard_filter.federation_id' => $this->federation_id,
            'dashboard_filter.district_id' => $this->district_id,
            'dashboard_filter.church_id' => $this->church_id,
        ]);

        $this->dispatch('dashboard-filter-updated');
    }

    public function resetFilter(): void
    {
        $this->federation_id = null;
        $this->district_id = null;
        $this->church_id = null;
        session()->forget(['dashboard_filter.federation_id', 'dashboard_filter.district_id', 'dashboard_filter.church_id']);
        $this->form->fill();
        $this->dispatch('dashboard-filter-updated');
    }
}