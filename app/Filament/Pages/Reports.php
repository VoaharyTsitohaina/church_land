<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Property;

class Reports extends Page
{
    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.reports';

    protected static ?string $title = 'Reports';

    public ?int $federation_id = null;
    public ?int $district_id = null;
    public ?int $church_id = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('federation_id')
                    ->label('Federation')
                    ->relationship('federation', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('district_id', null);
                        $set('church_id', null);
                    })
                    ->required(),
                Select::make('district_id')
                    ->label('District')
                    ->relationship(
                        name: 'district', 
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('federation_id', $get('federation_id'))
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('church_id', null);
                    })
                    ->required(),
                Select::make('church_id')
                    ->label('Church')
                    ->relationship(
                        name: 'church', 
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('district_id', $get('district_id'))
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
            ])->columns(3);
    }

    protected function baseQuery(): Builder
    {
        return Property::query()
            ->when($this->church_id, fn ($q) => $q->where('church_id', $this->church_id))
            ->when($this->district_id && !$this->church_id, fn ($q) => $q->whereHas('church', fn ($q2) => $q2->where('district_id', $this->district_id)))
            ->when($this->federation_id && !$this->district_id, fn ($q) => $q->whereHas('church.district', fn ($q2) => $q2->where('federation_id', $this->federation_id)));

    }

    public function getViewData(): array
    {
        return [
            'byFederation' => (clone $this->baseQuery())
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->join('districts', 'churches.district_id', '=', 'districts.id')
                ->join('federations', 'districts.federation_id', '=', 'federations.id')
                ->select('federations.name', DB::raw('count(properties.id) as total'))
                ->groupBy('federations.name')
                ->get(),
        ];
    }
}
