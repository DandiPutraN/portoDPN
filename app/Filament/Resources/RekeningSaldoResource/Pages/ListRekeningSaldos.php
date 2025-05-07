<?php

namespace App\Filament\Resources\RekeningSaldoResource\Pages;

use Filament\Actions;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\RekeningSaldoResource;
use App\Filament\Resources\RekeningSaldoResource\Widgets\RekeningSaldoStats;

class ListRekeningSaldos extends ListRecords
{
    protected static string $resource = RekeningSaldoResource::class;

    protected ?string $heading = 'Bank';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->label('Transaksi Baru')
            ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RekeningSaldoStats::class
        ];
    }
}
