<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\AssetResource;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\Support\Htmlable;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    public function getTitle(): string | Htmlable
    {
        /** @var Post */
        $record = $this->getRecord();

        return $record->nomor;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('penyusutan')
            ->label('Jalankan Penyusutan')
            ->color('warning')
            ->icon('heroicon-o-play-circle')
            ->requiresConfirmation()
            ->action(function ($record) {
                // Pastikan aset dapat disusutkan
                if ($record->status !== 'terdaftar') {
                    Notification::make()
                        ->title('Penyusutan Gagal')
                        ->body('Aset ini tidak memiliki status "terdaftar" dan tidak dapat disusutkan.')
                        ->warning()
                        ->send();
                    return;
                }
        
                // Hitung nilai penyusutan untuk aset ini
                $penyusutan = static::hitungPenyusutan($record);
        
                // Buat record di TransactionAsset (AssetItem)
                \App\Models\AssetItem::create([
                    'asset_id' => $record->id,
                    'tgl' => now(),
                    'account_id' => $record->akun_penyusutan,
                    'kredit' => $penyusutan ?? 0,
                ]);
        
                Notification::make()
                    ->title('Penyusutan Berhasil')
                    ->body('Aset telah disusutkan dan dicatat dalam transaksi.')
                    ->success()
                    ->send();
            }),
        

            Actions\Action::make('jual_aset')
            ->label('Jual/Lepas Aset')
            ->color('danger')
            ->icon('heroicon-o-calculator')
            ->form([
                Section::make('Detail Penjualan/Pelepasan Aset')->schema([
                    Grid::make(6)->schema([
                        Select::make('account_id')
                            ->relationship('account', 'id', fn ($query) => $query->whereBetween('id', [800, 809]))
                            ->label('Akun Penjualan/Pelepasan Aset')
                            ->searchable()
                            ->columnSpan(6) // Lebih besar dari sebelumnya (3)
                            ->required()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => '1-' . str_pad($record->id, 5, '0', STR_PAD_LEFT) . ' - ' . $record->nama),

                        DatePicker::make('tgl_pelepasan')
                        ->label('Tanggal')
                        ->default(now())
                        ->required()
                        ->columnSpan(2), // Lebih besar dari sebelumnya (1)
        
                        TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp.')
                            ->required()
                            ->columnSpan(4), // Full width untuk visibilitas lebih baik
                    ]),
                ])->columnSpanFull(), // Menyesuaikan dengan lebar penuh
            ])
            ->modalWidth('xl')// Menambah lebar modal jika diperlukan        
            ->action(function (array $data, $record) {
                if (!$record) {
                    Notification::make()
                        ->title('Pilih Aset Terlebih Dahulu')
                        ->body('Silakan pilih aset dari daftar sebelum menjualnya.')
                        ->warning()
                        ->send();
                    return;
                }
        
                if (empty($data['harga_jual'])) {
                    Notification::make()
                        ->title('Harga Jual Diperlukan')
                        ->body('Mohon isi harga jual sebelum melepas aset.')
                        ->danger()
                        ->send();
                    return;
                }
        
                // Update status aset ke 'dilepas'
                $record->update([
                    'status' => 'dilepas',
                    'harga_jual' => $data['harga_jual'],
                ]);
        
                // Buat record baru di TransactionAsset
                \App\Models\AssetItem::create([
                    'asset_id' => $record->id,
                    'tgl' => now(),
                    'account_id' => $data['account_id'],
                    'debit' => $data['harga_jual'],
                    'kredit' => 0,
                ]);
        
                Notification::make()
                    ->title('Aset Berhasil Dilepas')
                    ->body("Aset {$record->nama} telah dijual dengan harga Rp " . number_format($data['harga_jual'], 0, ',', '.'))
                    ->success()
                    ->send();
            }),
        
        ];
    }

    public static function hitungPenyusutan($asset)
    {
        $penyusutanData = 0;
    
        // Pastikan aset dapat disusutkan
        if ($asset->nilai_buku > $asset->nilai_residu) {
            $penyusutan = $asset->hitungPenyusutanTahunan(); // Ambil nilai penyusutan
    
            $nilaiSebelum = $asset->nilai_buku;
            $asset->nilai_buku = max($asset->nilai_residu, $nilaiSebelum - $penyusutan);
            $asset->save();
    
            $penyusutanData = $nilaiSebelum - $asset->nilai_buku; // Simpan pengurangan nilai
        }
    
        return $penyusutanData;
    }
    
}
