<?php

namespace App\Filament\Resources\SaldoResource\Pages;

use Filament\Pages\Actions\Action;
use Filament\Pages\Actions\CreateAction;
use App\Filament\Resources\SaldoResource;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\SaldoResource\Widgets\SaldoStats;

class ListSaldos extends ListRecords
{
    protected static string $resource = SaldoResource::class;

    protected ?string $heading = 'Petty Cash';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->label('Transaksi Baru')
            ->icon('heroicon-o-plus'),
            // Action::make('laporan')
            //     ->label('Lihat Laporan')
            //     ->icon('heroicon-o-document-text') // Icon untuk tombol
            //     ->url(route('laporan.kas'))
            //     ->openUrlInNewTab(), // Membuka di tab baru
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SaldoStats::class
        ];
    }
}
