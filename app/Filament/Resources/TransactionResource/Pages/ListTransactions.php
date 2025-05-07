<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\TransactionResource\Widgets\TransactionStats;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected ?string $heading = 'Entry Journal';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Transaksi Baru')
            ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TransactionStats::class
        ];
    }

     public function getTabs(): array
    {
        return [
            null => Tab::make('All'),

            'Pending' => Tab::make()->query(fn ($query) => $query->where('status', 'pending')),
            'Terbayar' => Tab::make()->query(fn ($query) => $query->where('status', 'paid')),

            'Lunas' => Tab::make('Lunas')
                ->query(fn ($query) => $query->where('lunas', false)),

            'Belum Lunas' => Tab::make('Belum Lunas')
                ->query(fn ($query) => $query->where('lunas', true)),
            
            'Tagihan' => Tab::make('Tagihan')
                ->query(fn ($query) => $query->where('lunas', true)->where(function ($query) {
                    // Exclude transactions that are overdue
                    $query->where('jatuh_tempo', '>=', now())->orWhereNull('jatuh_tempo');
                })),

            'Jatuh Tempo (Belum Lunas)' => Tab::make('Jatuh Tempo')
                ->query(fn ($query) => $query->where('lunas', true)->where('jatuh_tempo', '<', now())),

            // 'Unpaid' => Tab::make()->query(fn ($query) => $query->where('status', 'unpaid')),
        ];
    }
}
