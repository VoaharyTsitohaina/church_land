<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Property;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;
use App\Exports\PropertiesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Livewire\Attributes\On;
use App\Filament\Concerns\ScopesPropertiesByUser;

class Reports extends Page
{
    use HasPageShield, ScopesPropertiesByUser;

    protected static ?string $navigationLabel = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.reports';
    protected static ?string $title = 'Reports';

    #[On('reports-filter-updated')]
    public function refresh(): void
    {
        // vide : force Livewire à relire la session au re-render
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Property::query();
 
        if ($user->hasRole('district_manager')) {
            $query->whereHas('church', fn ($q) => $q->where('district_id', $user->district_id));
        } elseif ($user->hasRole('federation_admin')) {
            $query->whereHas('church.district', fn ($q) => $q->where('federation_id', $user->federation_id));
        }
 
        $churchId = session('reports_filter.church_id');
        $districtId = session('reports_filter.district_id');
        $federationId = session('reports_filter.federation_id');
 
        if ($churchId) {
            $query->where('church_id', $churchId);
        } elseif ($districtId) {
            $query->whereHas('church', fn ($q) => $q->where('district_id', $districtId));
        } elseif ($federationId) {
            $query->whereHas('church.district', fn ($q) => $q->where('federation_id', $federationId));
        }
 
        return $query;
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
            'scopeLabel' => $this->currentScopeLabel(),
        ]);

        return response()->streamDownload(
            fn () => print(Pdf::loadView('reports.patrimoine', $data)->output()), 'rapport-patrimoine.pdf'
        );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ReportsFilterWidget::class,
        ];
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