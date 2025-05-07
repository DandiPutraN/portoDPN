<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Models\Asset;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Notifications\Notification;
use App\Filament\Resources\AssetResource;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\AssetResource\Widgets\AssetStats;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Tambah Aset Tetap')
            ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AssetStats::class
        ];
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('All'),
            
            'Draft' => Tab::make()->query(fn ($query) => $query->where('status', 'tersedia')),
            'Terdaftar' => Tab::make()->query(fn ($query) => $query->where('status', 'terdaftar')),
            'Dijual/Dilepas' => Tab::make()->query(fn ($query) => $query->where('status', 'dilepas')),
        ];
    }
}
