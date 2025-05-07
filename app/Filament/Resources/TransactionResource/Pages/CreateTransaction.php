<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected ?string $heading = 'Entry Journal';

    protected function getRedirectUrl(): string
    {
    return $this->getResource()::getUrl('index');
    }

    // protected function afterCreate(): void
    // {
    //     $transaction = $this->record;
    
    //     // Ambil user dengan role "super_admin"
    //     $admins = User::role('super_admin')->get();
    
    //     // Ambil item pertama dari transaksi (sesuaikan dengan relasi yang digunakan)
    //     $item = $transaction->items()->first(); 
    
    //     // Pastikan item dan kategori tidak null untuk menghindari error
    //     $categoryName = $item?->category?->nama ?? 'Kategori Tidak Diketahui';
    
    //     foreach ($admins as $admin) {
    //         Notification::make()
    //             ->title('New Transaction')
    //             ->icon('heroicon-o-banknotes')
    //             ->body("**{$transaction->penerima} - {$categoryName}** telah melakukan transaksi baru.")
    //             ->actions([
    //                 Action::make('Edit')
    //                     ->url(TransactionResource::getUrl('edit', ['record' => $transaction->id])),
    //             ])
    //             ->sendToDatabase($admin);
    //     }
    // }    
    
}
