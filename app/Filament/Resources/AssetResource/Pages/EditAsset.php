<?php

namespace App\Filament\Resources\AssetResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\AssetResource;
use Illuminate\Contracts\Support\Htmlable;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string | Htmlable
    {
        /** @var Post */
        $record = $this->getRecord();

        return $record->nomor;
    }
    
    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
