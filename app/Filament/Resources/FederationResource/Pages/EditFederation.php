<?php

namespace App\Filament\Resources\FederationResource\Pages;

use App\Filament\Resources\FederationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFederation extends EditRecord
{
    protected static string $resource = FederationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
