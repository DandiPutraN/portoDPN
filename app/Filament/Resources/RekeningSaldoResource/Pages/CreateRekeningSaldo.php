<?php

namespace App\Filament\Resources\RekeningSaldoResource\Pages;

use App\Filament\Resources\RekeningSaldoResource;
use Illuminate\Database\Eloquent\Model;
use App\Models\RekeningSaldo;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRekeningSaldo extends CreateRecord
{
    protected static string $resource = RekeningSaldoResource::class;

    protected ?string $heading = 'Bank';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Pisahkan saldo_items dari data utama
        $rekeningsaldo_items = $data['rekeningsaldo_items'] ?? [];
        unset($data['rekeningsaldo_items']); 
    
        // Buat Saldo (data utama)
        $rekeningsaldo = RekeningSaldo::create($data);
    
        // Simpan Saldo Items
        if (!empty($rekeningsaldo_items)) {
            foreach ($rekeningsaldo_items as $item) {
                // Validasi item
                if (!empty($item['account_id'])) {
                    $rekeningsaldo->rekeningsaldo_items()->create([
                        'account_id' => $item['account_id'],
                        'biaya' => $item['biaya'] ?? 0,
                        'keterangan' => $item['keterangan'] ?? '',
                    ]);
                }
            }
        }
    
        // Muat ulang relasi rekeningsaldo_items
        $rekeningsaldo->load('rekeningsaldo_items');
    
        return $rekeningsaldo;
    }
}
