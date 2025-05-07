<?php

namespace App\Filament\Resources\RekeningSaldoResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\RekeningSaldoResource;

class EditRekeningSaldo extends EditRecord
{
    protected static string $resource = RekeningSaldoResource::class;

    public function getTitle(): string | Htmlable
    {
        /** @var Post */
        $record = $this->getRecord();

        return $record->nomor;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
