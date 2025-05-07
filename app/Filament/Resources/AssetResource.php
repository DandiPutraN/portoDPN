<?php

namespace App\Filament\Resources;

use App\Models\Asset;
use App\Models\User;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Infolists\Components;
use Filament\Resources\Pages\Page;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Resources\AssetResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AssetResource\RelationManagers;
use App\Filament\Resources\AssetResource\RelationManagers\AssetItemRelationManager;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Manajemen Aset';

    protected static ?string $pluralModelLabel = 'Aset Tetap';

    protected static ?string $navigationLabel = 'Aset Tetap';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
                Group::make()->schema([
                    Section::make('Manajemen Aset')
                    ->description('Account Number:')
                    ->schema([
                        Placeholder::make('nomor')
                        ->label('')
                        ->content(fn ($record) => $record?->nomor ?? 'Belum tersedia')
                        ->extraAttributes(['class' => 'text-lg font-bold'])
                        ->columnSpanFull(),

                        Forms\Components\TextInput::make('nama_aset')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('tgl_pembelian')
                            ->label('Tanggal Pembelian')
                            ->default(now())
                            ->required()
                            ->visible(fn ($get) => in_array($get('status'), ['tersedia', 'terdaftar'])),                    

                        Forms\Components\DatePicker::make('tgl_pelepasan')
                        ->label('Tanggal Pelepasan')
                        ->required()
                        ->visible(fn ($get) => $get('status') === 'dilepas'),

                        Forms\Components\BelongsToSelect::make('account_id')
                        ->relationship('account', 'id', fn ($query) => $query->whereIn('id', [41, 42, 43, 44, 45, 46, 47, 48]))
                        ->label('Akun Aset Tetap')
                        ->searchable()
                        ->required()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            return '1-' . str_pad($record->id, 5, '0', STR_PAD_LEFT) . ' - ' . $record->nama;
                        }),

                        Forms\Components\TextInput::make('lokasi')
                        ->required()
                        ->maxLength(255),

                        Forms\Components\TextInput::make('harga_beli')
                        ->label('Harga Beli')
                        ->required()
                        ->prefix('Rp.')
                        ->numeric()
                        ->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord), // Disable di Create

                        Forms\Components\TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->required()
                            ->prefix('Rp.')
                            ->numeric()
                            ->visible(fn ($get) => $get('status') === 'dilepas'),

                        Forms\Components\Textarea::make('keterangan')
                        ->placeholder('Optional')
                        ->columnSpanFull(),

                    ])->columns(2),
                ])->columnSpan(4),
                
                Group::make()->schema([
                    Section::make('Status')->schema([
                        ToggleButtons::make('status')
                            ->label('')
                            ->options([
                                'tersedia' => 'Tersedia',
                                'terdaftar' => 'Terdaftar',
                                'dilepas' => 'Dilepas',
                            ])
                            ->icons([
                                'tersedia' => 'heroicon-o-check-circle',  // Ikon untuk status tersedia
                                'terdaftar' => 'heroicon-o-check-circle',  // Ikon untuk status tersedia
                                'dilepas' => 'heroicon-o-trash',  // Ikon untuk status dilepas
                            ])
                            ->colors([
                                'tersedia' => 'info', // Hijau
                                'terdaftar' => 'success', // Hijau
                                'dilepas' => 'danger', // Merah
                            ])
                            ->default('tersedia')
                            ->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord) // Disable di Create
                            ->reactive()
                    ])->columns(2),
                ])->columnSpan(1),

                Forms\Components\Toggle::make('is_penyusutan')
                ->label('Aset memiliki penyusutan?')
                ->default(false)
                ->reactive()
                ->columnSpanFull()
                ->visible(fn ($get) => in_array($get('status'), ['tersedia', 'terdaftar']))
                ->required(),            

                Group::make()->schema([
                    Section::make('Penyusutan')
                    ->schema([
                        Forms\Components\Select::make('akumulasi_penyusutan')
                        ->label('Akun Akumulasi Penyusutan')
                        ->required()
                        ->preload()
                        ->columnSpan(6)
                        ->searchable()
                        ->placeholder('Pilih Akun Penyusutan')
                        ->reactive()
                        ->options(function () {
                            return \App\Models\Account::whereIn('id', [400, 401, 402, 403, 404, 405, 406])
                                ->get()
                                ->mapWithKeys(function ($account) {
                                    return [
                                        '1-' . str_pad($account->id, 5, '0', STR_PAD_LEFT) . 
                                        ' - ' . $account->nama 
                                        => 
                                        '1-' . str_pad($account->id, 5, '0', STR_PAD_LEFT) . 
                                        ' - ' . $account->nama
                                    ];
                                })
                                ->toArray();
                        })
                        ->getSearchResultsUsing(fn (string $query) => 
                            \App\Models\Account::whereIn('id', [400, 401, 402, 403, 404, 405, 406])
                                ->where('nama', 'like', "%{$query}%")
                                ->orWhere('kategori', 'like', "%{$query}%")
                                ->get()
                                ->mapWithKeys(function ($account) {
                                    return [
                                        '1-' . str_pad($account->id, 5, '0', STR_PAD_LEFT) . 
                                        ' - ' . $account->nama
                                        => 
                                        '1-' . str_pad($account->id, 5, '0', STR_PAD_LEFT) . 
                                        ' - ' . $account->nama
                                    ];
                                })
                                ->toArray()
                        )
                        ->getOptionLabelUsing(fn ($value) => $value),

                        Forms\Components\Radio::make('metode_penyusutan')
                        ->options([
                            'straight line' => 'Straight Line',
                            'declining balance' => 'Declining Balance',
                        ])
                        ->required()
                        ->default('straight line')
                        ->inline()
                        ->inlineLabel(false)
                        ->columnSpan(6)
                        ->label('Metode Penyusutan')
                        ->reactive(),
                    
                        // Forms\Components\Select::make('akumulasi_penyusutan')
                        //     ->relationship('account', 'id', fn ($query) => $query->whereIn('id', [400, 401, 402, 403, 404, 405, 406]))
                        //     ->label('Akumulasi Penyusutan')
                        //     ->searchable()
                        //     ->required()
                        //     ->columnSpan(3)
                        //     ->preload()
                        //     ->getOptionLabelFromRecordUsing(function ($record) {
                        //         return '1-' . str_pad($record->id, 5, '0', STR_PAD_LEFT) . ' - ' . $record->nama;
                        // }),      
                        
                        Forms\Components\BelongsToSelect::make('akun_penyusutan')
                        ->relationship('account', 'id', fn ($query) => $query->whereBetween('id', [100, 153]))
                        ->label('Akun Penyusutan')
                        ->searchable()
                        ->required()
                        ->columnSpan(6)
                        ->preload()
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            return '1-' . str_pad($record->id, 5, '0', STR_PAD_LEFT) . ' - ' . $record->nama;
                        }),

                        Forms\Components\TextInput::make('masa_manfaat')
                        ->label('Masa Manfaat')
                        ->columnSpan(2)
                        ->numeric()
                        ->suffix('Tahun')
                        ->reactive()
                        ->visible(fn (Get $get) => $get('metode_penyusutan') !== 'declining balance'),
                            
                        Forms\Components\TextInput::make('nilai_residu')
                            ->prefix('Rp.')
                            ->columnSpan(4)
                            ->numeric()
                            ->visible(fn (Get $get) => $get('metode_penyusutan') !== 'declining balance'),

                        Forms\Components\TextInput::make('presentase_penyusutan')
                        ->label('Persentase Penyusutan')
                        ->columnSpan(3)
                        ->numeric()
                        ->suffix('%')
                        ->reactive()
                        ->visible(fn (Get $get) => $get('metode_penyusutan') !== 'straight line'),

                    ])
                    ->columns(12)
                    ->visible(fn ($get) => $get('is_penyusutan') === true),

                ])->columnSpan(5),
                
            ])->columns(5);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_aset')
                    ->label('Nama Aset')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nomor')
                    ->label('Nomor Aset')
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tgl_pembelian')
                    ->label('Tanggal Pembelian')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->numeric()
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                ->label('Nomor')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'tersedia' => 'info', // Hijau
                    'terdaftar' => 'success', // Hijau
                    'dilepas' => 'danger', // Merah
                }),

                SelectColumn::make('status')
                ->searchable()
                ->sortable()
                ->options(fn ($record) => [
                    'tersedia' => 'Draft',
                    'terdaftar' => 'Terdaftar',
                    ...($record->status === 'dilepas' ? ['dilepas' => 'Dilepas'] : []), // Tampilkan "Dilepas" hanya jika status sudah dilepas
                ])
                ->disabled(fn ($record) => $record->status === 'dilepas'), // Kunci jika status sudah dilepas

                Tables\Columns\TextColumn::make('nilai_buku')
                ->money('IDR')
                ->tooltip('Nilai Buku Setelah Penyusutan')
                ->label('Nilai Buku'),

                // Tables\Columns\TextColumn::make('masa_manfaat')
                //     ->label('Masa Manfaat')
                //     ->sortable(),

                //     Tables\Columns\TextColumn::make('presentase_penyusutan')
                // ->label('Persentase (%)'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('created_at')
                ->form([
                    Forms\Components\DatePicker::make('created_from'),
                    Forms\Components\DatePicker::make('created_until'),
                ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->label('Setting'),
                Tables\Actions\BulkAction::make('penyusutan')
                ->color('warning')
                ->label('Jalankan Penyusutan')
                ->icon('heroicon-o-play-circle')
                ->requiresConfirmation()
                ->action(function ($records) {
                    // Pisahkan aset yang bisa dan tidak bisa disusutkan
                    $dapatDisusutkan = $records->filter(fn ($record) => $record->status === 'terdaftar');
                    $tidakDapatDisusutkan = $records->filter(fn ($record) => $record->status !== 'terdaftar');
            
                    // Jalankan penyusutan dan ambil hasilnya
                    if ($dapatDisusutkan->isNotEmpty()) {
                        $penyusutanData = static::hitungPenyusutanBulk($dapatDisusutkan);
            
                        // Buat record di TransactionAsset untuk setiap aset yang disusutkan
                        foreach ($dapatDisusutkan as $record) {
                            \App\Models\AssetItem::create([
                                'asset_id' => $record->id,
                                'tgl' => now(),
                                'account_id' => $record->akun_penyusutan,
                                'debit' => 0, // Gunakan penyusutan yang dihitung
                                'kredit' => $penyusutanData[$record->id] ?? 0, // Gunakan penyusutan yang dihitung
                            ]);
                        }
            
                        Notification::make()
                            ->title('Penyusutan Berhasil')
                            ->body(count($dapatDisusutkan) . ' aset telah disusutkan dan dicatat dalam transaksi.')
                            ->success()
                            ->send();
                    }
            
                    // Notifikasi untuk aset yang tidak dapat disusutkan
                    if ($tidakDapatDisusutkan->isNotEmpty()) {
                        Notification::make()
                            ->title('Beberapa Aset Tidak Disusutkan')
                            ->body(count($tidakDapatDisusutkan) . ' aset tidak memiliki status "terdaftar" dan tidak dapat disusutkan.')
                            ->warning()
                            ->send();
                    }
                })
                ->deselectRecordsAfterCompletion(),
            
                // Tables\Actions\BulkAction::make('penyusutan')
                // ->color('warning')
                // ->label('Jalankan Penyusutan')
                // ->icon('heroicon-o-play-circle')
                // ->requiresConfirmation()
                // ->action(function ($records) {
                //     // Pisahkan aset yang bisa dan tidak bisa disusutkan
                //     $dapatDisusutkan = $records->filter(fn ($record) => $record->status === 'terdaftar');
                //     $tidakDapatDisusutkan = $records->filter(fn ($record) => $record->status !== 'terdaftar');
            
                //     // Jalankan penyusutan hanya untuk aset yang memenuhi syarat
                //     if ($dapatDisusutkan->isNotEmpty()) {
                //         static::hitungPenyusutanBulk($dapatDisusutkan);
            
                //         // Tampilkan notifikasi sukses jika ada aset yang diproses
                //         Notification::make()
                //             ->title('Penyusutan Berhasil')
                //             ->body(count($dapatDisusutkan) . ' aset telah disusutkan.')
                //             ->success()
                //             ->send();
                //     }
            
                //     // Jika ada aset yang tidak bisa disusutkan, tampilkan notifikasi peringatan
                //     if ($tidakDapatDisusutkan->isNotEmpty()) {
                //         Notification::make()
                //             ->title('Beberapa Aset Tidak Disusutkan')
                //             ->body(count($tidakDapatDisusutkan) . ' aset tidak memiliki status "terdaftar" dan tidak dapat disusutkan.')
                //             ->warning()
                //             ->send();
                //     }
                // })
                // ->deselectRecordsAfterCompletion(),   
                         
            ])
            ->headerActions([
                Tables\Actions\Action::make('penyusutan')
                ->label('Hitung Penyusutan')
                ->color('warning')
                ->icon('heroicon-o-calculator')
                ->action(fn () => Notification::make()
                    ->title('Pilih Aset Terlebih Dahulu')
                    ->body('Silakan pilih aset dari daftar, lalu tekan "Jalankan Penyusutan".')
                    ->warning()
                    ->send()
                ),
            ])
            ->groups([
                Tables\Grouping\Group::make('created_at')
                    ->label('Transaction Date')
                    ->date()
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AssetItemRelationManager::class
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Detail')
                    ->schema([
                        Components\Split::make([
                            Components\Grid::make(3)
                                ->schema([
                                    Components\Group::make([
                                        Components\TextEntry::make('nama_aset')
                                        ->label('Nama Aset')
                                        ->weight(FontWeight::Bold),
                                        Components\TextEntry::make('tgl_pembelian')
                                            ->label('Tanggal Pembelian')
                                            ->color('info')
                                            ->date('d/m/Y'),
                                        Components\TextEntry::make('account.nama')
                                        ->label('Akun Aset Tetap'),
                                    ]),

                                    Components\Group::make([
                                        Components\TextEntry::make('tgl_pelepasan')
                                        ->label('Tanggal Pelepasan/Jual')
                                        ->color('danger')
                                        ->date('d/m/Y')
                                        ->hidden(fn ($record) => empty($record->tgl_pelepasan)),                                

                                        Components\TextEntry::make('lokasi'),

                                        Components\TextEntry::make('status')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'tersedia' => 'info', // Hijau
                                            'terdaftar' => 'success', // Hijau
                                            'dilepas' => 'danger', // Merah
                                        }),
                                        Components\TextEntry::make('keterangan'),
                                    ]),

                                    Components\Group::make([
                                        Components\TextEntry::make('harga_beli')
                                        ->label('Harga Beli')
                                        ->money('IDR'),

                                        Components\TextEntry::make('harga_jual')
                                        ->label('Harga Pelepasan/Jual')
                                        ->color('danger')
                                        ->money('IDR')
                                        ->hidden(fn ($record) => empty($record->harga_jual)),                                


                                        Components\TextEntry::make('nilai_buku')
                                        ->label('Nilai Buku')
                                        ->tooltip('Nilai Buku Setelah Penyusutan')
                                        ->money('IDR'),

                                        Components\IconEntry::make('is_penyusutan')
                                        ->label('Penyusutan')
                                        ->boolean()
                                    ]),
                                ]),
                        ])->from('lg'),
                    ]),
                Components\Section::make('Penyusutan')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\Group::make([
                                    Components\TextEntry::make('akumulasi_penyusutan')
                                    ->label('Akun Akumulasi Penyusutan')
                                    ->hidden(fn ($record) => empty($record->akumulasi_penyusutan)),                                

                                    // Components\TextEntry::make('akun_penyusutan')
                                    // ->label('Akun Penyusutan')
                                    // ->formatStateUsing(fn ($state, $record) => 
                                    //     $record->account?->id . ' - ' . $record->account?->nama ?? 'Tidak ada penyusutan'
                                    // )
                                    // ->hidden(fn ($record) => empty($record->akun_penyusutan)),
                                 
                                    
                                    Components\TextEntry::make('metode_penyusutan')
                                    ->badge()
                                    ->color('warning')
                                    ->label('Metode Penyusutan'),
                               
                                ]),

                                Components\Group::make([
                                    Components\TextEntry::make('masa_manfaat')
                                    ->label('Masa Manfaat')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state . ' Tahun')
                                    ->hidden(fn ($record) => empty($record->masa_manfaat)),                                

                                    Components\TextEntry::make('presentase_penyusutan')
                                    ->label('Persentase Penyusutan')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state . '%')
                                    ->hidden(fn ($record) => empty($record->presentase_penyusutan)),                                

                                    Components\TextEntry::make('nilai_residu')
                                    ->label('Nilai Residu')
                                    ->hidden(fn ($record) => empty($record->masa_manfaat)), 
                                ]),
                            ])
                ])
                ->collapsible(),
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewAsset::class,
            Pages\EditAsset::class,
            Pages\TransactionAsset::class,
        ]);
    }

    public static function hitungPenyusutanBulk($records)
    {
        $totalAset = count($records);
        $namaAset = [];
        $penyusutanData = [];
    
        foreach ($records as $asset) {
            $penyusutan = $asset->hitungPenyusutanTahunan();
    
            if ($asset->nilai_buku > $asset->nilai_residu) {
                $nilaiSebelum = $asset->nilai_buku; 
                $asset->nilai_buku = max($asset->nilai_residu, $nilaiSebelum - $penyusutan);
                $asset->save();
    
                $namaAset[] = $asset->nama_aset;
                $penyusutanData[$asset->id] = $nilaiSebelum - $asset->nilai_buku; // Simpan pengurangan nilai
            }
        }
    
        Notification::make()
            ->title('Penyusutan berhasil diperbarui!')
            ->body("Penyusutan aset telah berhasil diperbarui untuk beberapa aset.")
            ->success()
            ->send();
    
        // // **Notifikasi ke Admin & Panel User**
        // $namaAsetStr = implode(', ', $namaAset);
        // $users = User::whereHas('roles', function ($query) {
        //     $query->whereIn('name', ['super_admin', 'panel_user']);
        // })->get();
    
        // foreach ($users as $user) {
        //     Notification::make()
        //         ->title('Penyusutan Aset Diperbarui')
        //         ->body("Sebanyak **{$totalAset} aset** telah mengalami penyusutan.\n\n📋 **Daftar Aset:** {$namaAsetStr}.")
        //         ->success()
        //         ->sendToDatabase($user);
        // }
    
        return $penyusutanData; // Kembalikan nilai penyusutan per aset
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'transaksi' => Pages\TransactionAsset::route('/{record}/transaksi'),
            'view' => Pages\ViewAsset::route('/{record}'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
