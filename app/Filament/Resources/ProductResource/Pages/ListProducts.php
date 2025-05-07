<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use App\Imports\ProductImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ImportProduct')
                ->label('Import Product')
                ->icon('heroicon-s-arrow-down-tray')
                ->color('warning')
                ->form([
                    FileUpload::make('attachment')
                        ->required()
                        ->label('Upload Product Template (.xlsx)')                        
                        // ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheetapplication/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->rules([ 'mimes:xls,xlsx'])
                        ->minSize(1)
                        ->maxSize(1024)
                        ->disk('public')
                        ->directory('imported-template')
                        ->visibility('public')
                ])
                ->action(function(array $data)
                {
                    $file = public_path() . '/storage/'  . $data['attachment'];

                    try{
                        Excel::import(new ProductImport, $file);
                        Notification::make()
                            ->title('Product imported')
                            ->success()
                            ->send();
                    }catch(\Exception $e)
                    {
                        Notification::make()
                            ->title('Product failed to import')
                            ->danger()
                            ->send();
                    }

                    // remove temporary files and excel files after imported process 
                    $tempFiles = array_merge(Storage::files('livewire-tmp'), Storage::files('imported-template'));

                    foreach ($tempFiles as $file) {
                        Storage::delete($file);
                    }
                    
                }),
            // Action::make('Download Template')
            //     ->url(route('download-template'))
            //     ->color('danger'),
            Actions\CreateAction::make()
            ->icon('heroicon-o-plus'),
        ];
    }
}
