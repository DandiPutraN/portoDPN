<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class POSPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.p-o-s-page';

    protected static ?string $navigationGroup = 'Marketing';
    
    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Point of Sales (POS)';
}
