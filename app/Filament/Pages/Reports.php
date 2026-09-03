<?php

namespace App\Filament\Pages;

use App\Models\Church;
use App\Models\District;
use App\Models\Federation;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Property;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;
use App\Exports\PropertiesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\WithPagination;

class Reports extends Page
{
    use WithPagination;
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.reports';

    protected static ?string $title = 'Reports';

    public ?int $federation_id = null;
    public ?int $district_id = null;
    public ?int $church_id = null;

    public function mount(): void
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

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
    }

    public function form(Form $form): Form
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        return $form
            ->schema([
                Select::make('federation_id')
                    ->label('Federation')
                    ->options(Federation::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('district_id', null);
                        $set('church_id', null);
                    })
                    ->visible(fn () => !$user->hasRole(['district_manager', 'federation_admin'])),

                Select::make('district_id')
                    ->label('District')
                    ->options(fn (Get $get) => 
                        District::query()
                            ->when($get('federation_id'), fn ($query, $federationId) => $query->where('federation_id', $federationId))
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('church_id', null);
                    })
                    ->visible(fn () => !$user->hasRole('district_manager')),

                Select::make('church_id')
                    ->label('Church')
                    ->options(fn (Get $get) => 
                        Church::query()
                            ->when($get('district_id'), fn ($query, $districtId) => $query->where('district_id', $districtId))
                            ->when(!$get('district_id') && $get('federation_id'), fn ($query) => 
                                $query->whereHas('district', fn ($q) => $q->where('federation_id', $get('federation_id')))
                            )
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->live(),
            ])->columns(3);
    }

    protected function userScope(Builder $query): Builder
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole('district_manager')) {
            $query->whereHas('church', fn ($q) => $q->where('district_id', $user->district_id));
        } elseif ($user->hasRole('federation_admin')) {
            $query->whereHas('church.district', fn ($q) => $q->where('federation_id', $user->federation_id));
        }

        return $query;
    }

    protected function baseQuery(): Builder
    {
        $query = Property::query()
            ->when($this->church_id, fn ($q) => $q->where('church_id', $this->church_id))
            ->when($this->district_id && !$this->church_id, fn ($q) => $q->whereHas('church', fn ($q2) => $q2->where('district_id', $this->district_id)))
            ->when($this->federation_id && !$this->district_id, fn ($q) => $q->whereHas('church.district', fn ($q2) => $q2->where('federation_id', $this->federation_id)));

        return $this->userScope($query);
    }

    public function getViewData(): array
    {
        return [
            'totalProperties' => (clone $this->baseQuery())->count(),
            'totalPropertiesWithTitle' => (clone $this->baseQuery())->whereNotNull('land_title_number')->count(),
            'totalPropertiesWithoutTitle' => (clone $this->baseQuery())->whereNull('land_title_number')->count(),
            'totalPropertiesWithoutDocuments' => (clone $this->baseQuery())->whereDoesntHave('media')->count(),
            'totalValuedProperties' => (clone $this->baseQuery())->whereNotNull('estimated_value')->count(),
            'totalValues' => (clone $this->baseQuery())->whereNotNull('estimated_value')->sum('estimated_value')
        ];
    }    

    public function exportDistrictExcel()
    {
        $rows = $this->getViewData()['byDistrict']
            ->map(fn ($r) => [$r->label, $r->total])->toArray();
        
        return Excel::download(
            new ArrayExport($rows, ['District', 'Total de biens']),
            'patrimoine-par-district.xlsx'
        );
    }

    public function exportChurchExcel()
    {
        $rows = $this->byChurchQuery()
            ->get()
            ->map(fn ($r) => [$r->label, $r->total])->toArray();
        
        return Excel::download(
            new ArrayExport($rows, ['Église', 'Total de biens']),
            'patrimoine-par-eglise.xlsx'
        );
    }
 
    public function exportWithoutTitleExcel()
    {
        $query = (clone $this->baseQuery())->whereNull('land_title_number');
        return Excel::download(new PropertiesExport($query), 'biens-sans-titre.xlsx');
    }

    public function exportMissingDocumentsExcel()
    {
        $query = (clone $this->baseQuery())->whereDoesntHave('media');
        return Excel::download(new PropertiesExport($query), 'biens-sans-documents.xlsx');
    }

    public function exportStatsExcel()
    {
        $data = $this->getViewData();
        $rows = [
            ['Total des biens', $data['totalProperties']],
            ['Total des biens avec titre foncier', $data['totalPropertiesWithTitle']],
            ['Total des biens sans titre foncier', $data['totalPropertiesWithoutTitle']],
            ['Total des biens sans documents', $data['totalPropertiesWithoutDocuments']],
            ['Total des biens valorisés', $data['totalValuedProperties']],
            ['Valeur totale estimée', $data['totalValues']],
        ];
 
        return Excel::download(
            new ArrayExport($rows, ['Indicateur', 'Valeur']),
            'statistiques-generales.xlsx'
        );
    }

    public function exportAllExcel()
    {
        return Excel::download(new PropertiesExport($this->baseQuery()), 'patrimoine-complet.xlsx');
    }

    public function exportPdf()
    {
        $data = array_merge($this->getViewData(), [
            'properties' => (clone $this->baseQuery())->with(['church.district.federation', 'type'])->get(),
        ]);

        return response()->streamDownload(
            fn () => print(Pdf::loadView('reports.patrimoine', $data)->output()), 'rapport-patrimoine.pdf'
        );
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\ByFederationReportWidget::class,
            \App\Filament\Widgets\ByTypeReportWidget::class,
            \App\Filament\Widgets\ByDistrictReportWidget::class,
            \App\Filament\Widgets\ByChurchReportWidget::class,
            \App\Filament\Widgets\WithoutTitleReportWidget::class,
            \App\Filament\Widgets\MissingDocumentsReportWidget::class,
        ];
    }
}