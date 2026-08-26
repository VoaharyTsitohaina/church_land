<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LegalStatusStatsWidget extends BaseWidget
{
    // protected static ?string $pollingInterval = null;
    protected static ?int $sort = 2;
    protected ?string $heading = "État de complétude des dossiers";

    protected function requiredFields(): array
    {
        return [
            'area',
            'land_title_number',
            'cadastral_number',
            'legal_status',
            'acquisition_mode',
            'acquisition_date',
        ];
    }

    protected function getStats(): array
    {
        $fields = $this->requiredFields();

        $enRegle = Property::query();
        foreach ($fields as $field) {
            $enRegle->whereNotNull($field);
        }

        $nonRenseigne = Property::query();
        foreach ($fields as $field) {
            $nonRenseigne->whereNull($field);
        }

        $enRegleCount = $enRegle->count();
        $nonRenseigneCount = $nonRenseigne->count();
        $enCoursCount = Property::count() - $enRegleCount - $nonRenseigneCount;

        return [
            Stat::make('En règle', $enRegleCount)
                ->description('Tous les champs obligatoires renseignés')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('En cours', $enCoursCount)
                ->description('Dossier partiellement complété')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Non renseigné', $nonRenseigneCount)
                ->description('Aucune information foncière saisie')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}