<?php

namespace App\Filament\Clusters\Products\Resources\KontakResource\Pages;

use App\Filament\Clusters\Products\Resources\KontakResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKontak extends EditRecord
{
    protected static string $resource = KontakResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
