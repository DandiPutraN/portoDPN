<?php

namespace App\Filament\Resources\AssetResource\Widgets;

use App\Models\Asset;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class AssetStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Aset', Asset::count())
            ->description('Semua aset yang tersedia')
            ->color('primary')
            ->icon('heroicon-o-archive-box'),

            Stat::make('Aset Terdaftar', Asset::where('status', 'terdaftar')->count())
                ->description('Jumlah aset yang terdaftar')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Aset Dijual/Dilepas', Asset::where('status', 'dilepas')->count())
                ->description('Jumlah aset yang sudah dijual/dilepas')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
