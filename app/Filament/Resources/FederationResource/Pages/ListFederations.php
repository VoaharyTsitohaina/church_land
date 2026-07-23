<?php

namespace App\Filament\Resources\FederationResource\Pages;

use App\Filament\Resources\FederationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFederations extends ListRecords
{
    protected static string $resource = FederationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
