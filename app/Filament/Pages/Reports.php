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
use App\Models\Property;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;
use App\Exports\PropertiesExport;
use Barryvdh\DomPDF\Facade\Pdf;

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
                    ->options(Federation::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('district_id', null);
                        $set('church_id', null);
                    }),

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
                    }),

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
                ->select('federations.name as label', DB::raw('count(properties.id) as total'))
                ->groupBy('federations.name')
                ->get(),

            'byDistrict' => (clone $this->baseQuery())
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->join('districts', 'churches.district_id', '=', 'districts.id')
                ->select('districts.name as label', DB::raw('count(properties.id) as total'))
                ->groupBy('districts.name')
                ->get(),

            'byChurch' => (clone $this->baseQuery())
                ->join('churches', 'properties.church_id', '=', 'churches.id')
                ->select('churches.name as label', DB::raw('count(properties.id) as total'))
                ->groupBy('churches.name')
                ->get(),

            'sansTitre' => (clone $this->baseQuery())
                ->whereNull('land_title_number')
                ->with('church')->get(),
            
            'documentsManquants' => (clone $this->baseQuery())
                ->whereDoesntHave('media')
                ->with('church')->get(),

            'byType' => (clone $this->baseQuery())
                ->join('property_types', 'properties.property_type_id', '=', 'property_types.id')
                ->select(
                    'property_types.name as label',
                    DB::raw('COUNT(properties.id) as total')
                )
                ->groupBy('property_types.id', 'property_types.name')
                ->get(),

            'totalProperties' => (clone $this->baseQuery())->count(),
            'totalPropertiesWithTitle' => (clone $this->baseQuery())->whereNotNull('land_title_number')->count(),
            'totalPropertiesWithoutTitle' => (clone $this->baseQuery())->whereNull('land_title_number')->count(),
            'totalPropertiesWithoutDocuments' => (clone $this->baseQuery())->whereDoesntHave('media')->count(),
            'totalValuedProperties' => (clone $this->baseQuery())->whereNotNull('estimated_value')->count(),
            'totalValues' => (clone $this->baseQuery())->whereNotNull('estimated_value')->sum('estimated_value')
        ];
    }    
            
    public function exportFederationExcel()
    {
        $rows = $this->getViewData()['byFederation']
            ->map(fn ($r) => [$r->label, $r->total])->toArray();
        
        return Excel::download(
            new ArrayExport($rows, ['Fédération/Mission', 'Total de biens']),
            'patrimoine-par-federation.xlsx'
        );
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
        $rows = $this->getViewData()['byChurch']
            ->map(fn ($r) => [$r->label, $r->total])->toArray();
        
        return Excel::download(
            new ArrayExport($rows, ['Église', 'Total de biens']),
            'patrimoine-par-eglise.xlsx'
        );
    }

    public function exportTypeExcel()
    {
        $rows = $this->getViewData()['byType']
            ->map(fn ($r) => [$r->label ?? 'Non spécifié', $r->total])->toArray();
        
        return Excel::download(
            new ArrayExport($rows, ['Type de bien', 'Total de biens']),
            'patrimoine-par-type.xlsx'
        );
    }
 
    public function exportSansTitreExcel()
    {
        $query = (clone $this->baseQuery())->whereNull('land_title_number');
        return Excel::download(new PropertiesExport($query), 'biens-sans-titre.xlsx');
    }

    public function exportDocumentsManquantsExcel()
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
}