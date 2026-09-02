<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardFilterWidget;
use App\Filament\Widgets\LandTitleStatsWidget;
use App\Filament\Widgets\LegalStatusStatsWidget;
use App\Filament\Widgets\OrganizationStatsWidget;
use Filament\Pages\Dashboard as BasePage;
use Override;

class Dashboard extends BasePage
{
    #[Override]
    public function getWidgets(): array
    {
        return [
            DashboardFilterWidget::class,
            OrganizationStatsWidget::class,
            LandTitleStatsWidget::class,
            LegalStatusStatsWidget::class,
        ];
    }
}