<?php

namespace App\Filament\Resources\SaldoResource\Pages;

use App\Models\Saldo;
use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\SaldoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSaldo extends CreateRecord
{
    protected static string $resource = SaldoResource::class;

    protected ?string $heading = 'Petty Cash';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Pisahkan saldo_items dari data utama
        $saldo_items = $data['saldo_items'] ?? [];
        unset($data['saldo_items']); 
    
        // Buat Saldo (data utama)
        $saldo = Saldo::create($data);
    
        // Simpan Saldo Items
        if (!empty($saldo_items)) {
            foreach ($saldo_items as $item) {
                // Validasi item
                if (!empty($item['account_id'])) {
                    $saldo->saldo_items()->create([
                        'account_id' => $item['account_id'],
                        'biaya' => $item['biaya'] ?? 0,
                        'keterangan' => $item['keterangan'] ?? '',
                    ]);
                }
            }
        }
    
        // Muat ulang relasi saldo_items
        $saldo->load('saldo_items');
    
        return $saldo;
    }
    
      
}