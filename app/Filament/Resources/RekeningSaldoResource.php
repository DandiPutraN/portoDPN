<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Account;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\RekeningSaldo;
use Illuminate\Support\Number;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\BelongsToSelect;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\RekeningSaldoResource\Pages;
use App\Filament\Resources\RekeningSaldoResource\RelationManagers;

class RekeningSaldoResource extends Resource
{
    protected static ?string $model = RekeningSaldo::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Bank';

    protected static ?string $pluralModelLabel = 'Rekening';
    
    protected static ?string $navigationGroup = 'Kas & Bank';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Group::make()->schema([
                Section::make('Transaction')->schema([
                    Select::make('kas_bank')
                        ->label('Transaksi Baru')
                        ->required()
                        ->reactive()
                        ->default('Kirim Dana')
                        ->options([
                            'Kirim Dana' => 'Kirim Dana',
                            'Terima Dana' => 'Terima Dana',
                            'Transfer Dana' => 'Transfer Dana',
                        ]),
                
                        Select::make('transaction_id')
                        ->label('Penerima')
                        ->relationship('transaction', 'penerima') // Relasi ke tabel 'transaction'
                        ->options(function ($get) {
                            $query = \App\Models\Transaction::where('status', 'pending')
                                ->whereHas('penerima', function ($query) {
                                    $query->where('bayar_dari', '1-00002 - Rekening');
                                })
                                ->whereDoesntHave('rekeningsaldo');
                    
                            // Tambahkan transaksi yang sudah dipilih agar tetap tampil saat edit
                            if ($selectedTransactionId = $get('transaction_id')) {
                                $query->orWhere('id', $selectedTransactionId);
                            }
                    
                            return $query->pluck('penerima', 'id');
                        })
                        ->required()
                        ->reactive()
                        ->placeholder(function () {
                            $availableTransactions = \App\Models\Transaction::where('status', 'pending')
                                ->whereHas('penerima', function ($query) {
                                    $query->where('bayar_dari', '1-00002 - Rekening');
                                })
                                ->whereDoesntHave('rekeningsaldo')
                                ->exists();
                    
                            return $availableTransactions ? 'Pilih Transaksi' : 'Tidak ada Transaksi';
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                // Ambil transactionitems berdasarkan transaction_id yang dipilih
                                $transactionItems = \App\Models\transactionitem::where('transaction_id', $state)->get();
                                $rekeningsaldo_items = $transactionItems->map(fn ($item) => [
                                    'account_id' => $item->account_id,
                                    'biaya' => $item->biaya,
                                    'keterangan' => $item->keterangan,
                                ])->toArray();
                    
                                // Update rekeningsaldo items
                                $set('rekeningsaldo_items', $rekeningsaldo_items);
                    
                                // Ambil nilai 'penerima' dari transaksi yang dipilih
                                $transaction = \App\Models\Transaction::find($state);
                                if ($transaction) {
                                    $set('penerima', $transaction->penerima);
                                }
                            } else {
                                $set('rekeningsaldo_items', []);
                                $set('penerima', null);
                            }
                        })
                        ->required(fn ($get) => $get('kas_bank') === 'Kirim Dana') // Wajib diisi untuk Kirim Dana
                        ->visible(fn ($get) => $get('kas_bank') === 'Kirim Dana'),
                    
                     // Tampilkan hanya untuk Kirim Dana
                
                    // Untuk Terima Dana, gunakan kontak
                    Select::make('kontak_id')
                        ->label('Pengirim')
                        ->relationship('kontak', 'nama')
                        ->required(fn ($get) => $get('kas_bank') === 'Terima Dana') 
                        ->visible(fn ($get) => $get('kas_bank') === 'Terima Dana')
                        ->createOptionForm([
                                Group::make()->schema([
                                   Section::make('Kontak')->schema([
                                        TextInput::make('nama')
                                        ->required(),
                        
                                        TextInput::make('perusahaan')
                                        ->required(),
                        
                                        TextInput::make('email')
                                        ->required(),
                
                                        TextInput::make('telepon')
                                        ->numeric()
                                        ->required(),
                
                                        TextArea::make('alamat')
                                        ->required()
                                        ->columnSpanFull()
                        
                                        ])->columns(2) 
                                ])->columnSpan(5),
                
                                Group::make()->schema([
                                   Section::make('Kategori')->schema([
                                        ToggleButtons::make('kategori')
                                        ->inline()
                                        ->columnSpan(3)
                                        ->default('lainnya')
                                        ->options([
                                            'Vendor' => 'Vendor',
                                            'Pegawai' => 'Pegawai',
                                            'Lainnya' => 'Lainnya',
                                        ])
                                        ->colors([
                                            'Vendor' => 'warning',
                                            'Pegawai' => 'success',
                                            'Lainnya' => 'info',
                                        ])
                                        ->icons([
                                            'Vendor' => 'heroicon-m-user-group',
                                            'Pegawai' => 'heroicon-m-users',
                                            'Lainnya' =>  'heroicon-m-user',
                                        ])
                                        ->reactive()
                                        ->required(), 
                                        
                                    TextInput::make('lainnya')
                                    ->columnSpan(2)
                                    ->placeholder('Masukkan Kategori')
                                    ->required(fn ($get) => $get('kategori') === 'Lainnya')
                                    ->visible(fn ($get) => $get('kategori') === 'Lainnya'),
                                        
                                   ])->columns(5) 
                                ])->columnSpan(5),
                        ])
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalHeading('Tambah Kontak')
                                ->modalSubmitActionLabel('Tambah Kontak')
                                ->modalWidth('2xl');
                        }),                   
                    
                    Select::make('transfer_dana')
                        ->label('Ke')
                        ->visible(fn ($get) => $get('kas_bank') === 'Transfer Dana')
                        ->required(fn ($get) => $get('kas_bank') === 'Transfer Dana')
                        ->options([
                            'Petty Cash' => 'Petty Cash',
                            'Giro' => 'Giro',
                        ]),

                    TextInput::make('nomor')
                    ->disabled()
                    ->default(fn ($state, $record) => $record ? $record->nomor : app(\App\Models\RekeningSaldo::class)->generateKodeBank()),
                    
                    
                    DatePicker::make('tanggal_transaksi')
                    ->default(now())
                    ->label('Tanggal Transaksi')
                    ->required(),
                ])->columns(2),
            ])->columnSpanFull(),


            Group::make()->schema([
                Section::make('Detail items')->schema([
                    Repeater::make('rekeningsaldo_items')
                    ->relationship('rekeningsaldo_items')
                    ->label('Details Saldo')
                    ->schema([
                        BelongsToSelect::make('account_id')
                        ->relationship('account', 'id', fn ($query) => $query->where('kategori', '!=', 'Kas & Bank'))
                        ->label('Akun Biaya')
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            return '1-' . str_pad($record->id, 5, '0', STR_PAD_LEFT) . ' - ' . $record->nama;
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(5),
                
                        TextInput::make('biaya')
                        ->required()
                        ->numeric()
                        ->columnSpan(2)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            // Update total biaya ketika biaya berubah
                            $subtotal = collect($get('rekeningsaldo_items'))
                                ->sum(fn($item) => $item['biaya'] ?? 0);
                    
                            $set('subtotal', $subtotal);
                        }),
                
                        // Keterangan: Diambil dari `transactionitem->description`
                        TextInput::make('keterangan')
                            ->columnSpan(5),

                    ])->columns(12)
                    ->createItemButtonLabel('Tambah Item')
                    // Tambahkan konfigurasi tambahan
                    ->itemLabel(fn (array $state): ?string => 
                        $state['account_id'] ? 
                        Account::find($state['account_id'])?->nama : 
                        null
                    )
                    // Pastikan item bisa dihapus
                    ->deletable()
                    // Batasi jumlah item jika diperlukan
                    ->maxItems(10),

                        Section::make()->schema([
                            Placeholder::make('subtotal_placeholder')
                            ->label(fn ($get) => $get('kas_bank') === 'Terima Dana' ? 'Debit' : 'Kredit')
                            // ->required(fn ($get) => in_array($get('kas_bank'), ['Terima Dana', 'Kirim Dana', 'Transfer Dana']))
                            ->visible(fn ($get) => in_array($get('kas_bank'), ['Terima Dana', 'Kirim Dana', 'Transfer Dana']))
                            ->content(function (Get $get, Set $set) {
                                $total = 0;
                        
                                if (!$repeaters = $get('rekeningsaldo_items')) {
                                    $set('terbilang', 'Nol Rupiah'); // Jika tidak ada item, set terbilang menjadi 'Nol Rupiah'
                                    return Number::currency($total, 'IDR'); // Mengembalikan 0 jika tidak ada item
                                }
                        
                                // Hitung total dari repeaters
                                foreach ($repeaters as $key => $repeater) {
                                    $amount = (float) $get("rekeningsaldo_items.{$key}.biaya");
                                    $total += $amount;
                                }
                        
                                // Set subtotal atau saldo_rekening berdasarkan jenis transaksi
                                if ($get('kas_bank') === 'Terima Dana') {
                                    $set('subtotal', $total);
                                } else {
                                    $set('saldo_rekening', $total);
                                }
                        
                                // Mengonversi total menjadi teks terbilang
                                $terbilang = self::convertToWords($total) . ' Rupiah';
                                $set('terbilang', $terbilang);
                        
                                // Mengembalikan subtotal dalam format mata uang IDR
                                return Number::currency($total, 'IDR');
                            }),
                        
                            Placeholder::make('terbilang')
                                ->label('Terbilang')
                                ->content(function (Get $get) {
                                    // Menampilkan teks terbilang yang sudah dihitung
                                    return $get('terbilang');
                                }),

                            Section::make('Bukti Transaksi')->schema([
                                FileUpload::make('images')
                                ->label('*Optional')
                                ->directory('rekeningsaldos')
                                ->image()
                                ->imageEditor()
                                ->columnSpanFull()
                                ->imageEditorAspectRatios([
                                    '16:9',
                                    '4:3',
                                    '1:1',
                                    ])
                            ])
                        ])->columns(2)
                ]),
            ])->columnSpanFull(),

            // Section::make()->schema([
            //     FileUpload::make('images')
            //         ->label('Bukti Transaksi')
            //         ->multiple() // Mengizinkan unggah banyak gambar
            //         ->directory('uploads/bukti-transaksi') // Lokasi penyimpanan file
            //         ->image() // Hanya menerima file gambar
            //         ->maxSize(2048) // Maksimum ukuran file per gambar (dalam KB)
            //         ->maxFiles(5) // Batas maksimum jumlah gambar
            //         ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']) // Format file yang diperbolehkan
            //         ->helperText('Unggah hingga 5 gambar dengan format .jpg, .jpeg, atau .png.'),
            // ]),
            
            Hidden::make('user_id')
            ->default(1),

            Hidden::make('subtotal')
            ->default(0),
            
            Hidden::make('saldo_rekening'),

            Hidden::make('terbilang'),

            Hidden::make('penerima'),

    ])->columns(6);
    }

    // Custom notification setelah berhasil membuat
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kirim Dana Berhasil Dicatat';
    }

    protected static function convertToWords($number)
    {
        // Check if the input is a string and convert it to a float
        if (is_string($number)) {
            $number = floatval($number);
        }
    
        // Handle negative numbers
        if ($number < 0) {
            return 'Minus ' . self::convertToWords(abs($number));
        }
    
        // Handle zero
        if ($number == 0) {
            return 'Nol';
        }
    
        // Split the number into whole and decimal parts
        $wholePart = floor($number);
        $decimalPart = round(($number - $wholePart) * 100); // Get the cents
    
        $units = ['', 'Ribu', 'Juta', 'Miliar', 'Triliun'];
        $words = [
            0 => '', 1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat', 5 => 'Lima',
            6 => 'Enam', 7 => 'Tujuh', 8 => 'Delapan', 9 => 'Sembilan', 10 => 'Sepuluh',
            11 => 'Sebelas', 12 => 'Dua Belas', 13 => 'Tiga Belas', 14 => 'Empat Belas',
            15 => 'Lima Belas', 16 => 'Enam Belas', 17 => 'Tujuh Belas', 18 => 'Delapan Belas', 19 => 'Sembilan Belas',
        ];
    
        $result = '';
        $place = 0;
    
        // Convert the whole part to words
        while ($wholePart > 0) {
            $n = $wholePart % 1000;
            if ($n != 0) {
                $hundreds = (int)($n / 100);
                $remainder = $n % 100;
    
                $part = '';
    
                if ($hundreds > 0) {
                    $part .= ($hundreds == 1 && $place > 0 ? 'Seratus' : $words[$hundreds] . ' Ratus') . ' ';
                }
    
                if ($remainder > 0) {
                    if ($remainder < 20) {
                        $part .= $words[$remainder] . ' ';
                    } else {
                        $tens = (int)($remainder / 10);
                        $units_digit = $remainder % 10;
                        $part .= ($tens == 1 ? 'Sepuluh' : $words[$tens] . ' Puluh') . ' ';
                        if ($units_digit > 0) {
                            $part .= $words[$units_digit] . ' ';
                        }
                    }
                }
    
                $result = trim($part) . ' ' . $units[$place] . ' ' . $result;
            }
    
            $wholePart = (int)($wholePart / 1000);
            $place++;
        }
    
        // Handle the decimal part
        if ($decimalPart > 0) {
            $result .= ' dan ' . $decimalPart . '/100';
        }
    
        return trim($result);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            IconColumn::make('transaction_status_and_lunas') // Kolom baru untuk gabungan status dan lunas
            ->label('Status') // Nama kolom
            ->getStateUsing(function ($record) {
                // Gabungkan status transaksi dan status lunas menjadi satu nilai
                $status = $record->transaction ? $record->transaction->status : null; // Ambil status transaksi
                $lunas = $record->lunas; // Ambil status lunas
                
                // Tentukan kondisi berdasarkan status transaksi dan lunas
                if ($status === 'pending') {
                    return 'pending'; // Pending jika status transaksi 'pending'
                } elseif ($status === 'paid' && $lunas) {
                    return 'paid'; // Paid jika status transaksi 'paid' dan lunas true
                } elseif ($status === 'unpaid' && !$lunas) {
                    return 'unpaid'; // Unpaid jika status transaksi 'unpaid' dan lunas false
                } elseif ($record->kas_bank === 'Terima Dana') {
                    return 'terima_dana'; // Status untuk Terima Dana
                } elseif ($record->kas_bank === 'Transfer Dana') {
                    return 'transfer_dana'; // Status untuk Transfer Dana
                }
                return 'unknown'; // Default untuk status yang tidak diketahui
            })
            ->options([
                'heroicon-o-clock' => 'pending',         // Ikon untuk status pending
                'heroicon-o-check-circle' => 'paid',     // Ikon untuk status paid
                'heroicon-o-x-circle' => 'unpaid',      // Ikon untuk status unpaid
                'heroicon-o-wallet' => 'terima_dana',  // Ikon untuk Terima Dana
                'heroicon-o-credit-card' => 'transfer_dana',      // Ikon untuk Transfer Dana
                'heroicon-o-question-mark-circle' => 'unknown',    // Ikon untuk status unknown
            ])
            ->colors([
                'warning' => fn ($state) => $state === 'pending',    // Warna kuning untuk pending
                'success' => fn ($state) => $state === 'paid',       // Warna hijau untuk paid
                'danger' => fn ($state) => $state === 'unpaid',      // Warna merah untuk unpaid
                'primary' => fn ($state) => $state === 'terima_dana', // Warna kuning untuk Terima Dana
                'info' => fn ($state) => $state === 'transfer_dana', // Warna biru terang untuk Transfer Dana
                'gray' => fn ($state) => $state === 'unknown',       // Warna abu-abu untuk unknown
            ]),                                           
                
            TextColumn::make('tanggal_transaksi')
            ->date('d/F/Y')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('nomor')
            ->searchable()
            ->sortable(),
            
            TextColumn::make('transaction_penerima_kontak_transfer_dana')
            ->label('Nama') // Label kolom gabungan
            ->getStateUsing(fn ($record) => $record->transaction?->penerima ?? $record->kontak?->nama ?? $record->transfer_dana) // Mengambil salah satu nilai
            ->tooltip('Pengirim / Penerima'),

            TextColumn::make('kas_bank')
            ->label('Transaksi')
            ->searchable()
            ->sortable()
            ->badge()
            ->colors([
                'primary' => 'Terima Dana',
                'success' => 'Kirim Dana',
                'info' => 'Transfer Dana',
            ]),

            TextColumn::make('rekeningsaldo_items')
            ->label('Keterangan')
            ->formatStateUsing(function ($record) {
                // Mengambil data rekeningsaldo_items dan hanya menampilkan nama kategori
                $accounts = $record->rekeningsaldo_items->map(function ($item) {
                    return $item->account->nama; // Hanya menampilkan nama kategori
                })->join(' | '); // Menggabungkan hasilnya dengan koma
        
                // Membatasi hanya 5 kata saja
                $words = explode(' ', $accounts); // Mengubah string menjadi array kata
                if (count($words) > 5) {
                    // Menyambungkan 5 kata pertama dan menambahkan '...'
                    return implode(' ', array_slice($words, 0, 5)) . '...';
                }
        
                // Jika jumlah kata kurang atau sama dengan 5, tampilkan semuanya
                return $accounts;
            })
            ->tooltip(function ($record) {
                // Menampilkan seluruh teks dalam tooltip
                return $record->rekeningsaldo_items->map(function ($item) {
                    return $item->account->nama; // Menampilkan seluruh nama kategori
                })->join(' | '); // Gabungkan hasilnya
            }),                   
        
            TextColumn::make('subtotal')
                ->label('Debit')
                ->money('IDR')
                ->sortable(),

            TextColumn::make('saldo_rekening')
                ->label('Kredit')
                ->money('IDR')
                ->sortable(),

            TextColumn::make('subtotal')
                ->label('Debit')
                ->money('IDR')
                ->sortable()
                ->summarize(Sum::make()),
            
            TextColumn::make('saldo_rekening')
                ->label('Kredit')
                ->money('IDR')
                ->sortable()
                ->summarize(Sum::make()),

            TextColumn::make('created_at')
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    // Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('downloadBukti')
                    ->label('Bukti Transaksi')
                    ->icon('heroicon-o-photo')
                    ->url(fn ($record) => url('storage/' . strtolower($record->images)))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->images)),

                    Tables\Actions\Action::make('printVoucher')
                    ->label('Cetak Voucher')
                    ->icon('heroicon-o-printer')
                    ->action(function (RekeningSaldo $record) {
                        // Pastikan transaksi memuat status dari transaksi terkait
                        $transaction = $record->transaction; // atau mungkin $record->saldo_items jika terkait
                
                        // Periksa apakah kas_bank 'Kirim Dana' dan status transaksi adalah 'paid'
                        if ($record->kas_bank === 'Kirim Dana' && $transaction && $transaction->status === 'paid') {
                            return redirect()->route('rekeningsaldo.voucher', $record->id);
                        }
                
                        // Jika tidak valid, tampilkan pesan kesalahan
                        Notification::make()
                            ->title('Data tidak valid untuk cetak voucher')
                            ->body('Hanya data dengan "Kirim Dana" dan status "paid" yang dapat dicetak sebagai voucher.')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (RekeningSaldo $record) => $record->kas_bank === 'Kirim Dana' && $record->transaction && $record->transaction->status === 'paid'), 

                    Tables\Actions\Action::make('print')
                    ->label('Cetak Voucher')
                    ->icon('heroicon-o-printer')
                    ->action(function (RekeningSaldo $record) {
                        // Periksa apakah kas_bank bernilai 'Terima Dana'
                        if ($record->kas_bank === 'Terima Dana') {
                            return redirect()->route('rekeningsaldo.print', $record->id);
                        }
            
                        // Jika tidak, tampilkan pesan kesalahan
                        Notification::make()
                            ->title('Data tidak valid untuk cetak voucher')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (RekeningSaldo $record) => $record->kas_bank === 'Terima Dana'),
                
                    Tables\Actions\Action::make('markAsPaid')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($record) {
                        // Perbarui status transaksi jika ada hubungan dengan transaksi
                        if ($record->transaction) {
                            $record->transaction->update(['status' => 'paid']);
                        }
                        
                        $record->update(['lunas' => true]);

                        // Ambil data penerima dari transaksi
                        $penerima = $record->transaction->penerima ?? 'Penerima Tidak Diketahui';
            
                        // Ambil kategori dari item transaksi pertama (jika ada)
                        $item = $record->transaction->items()->first(); 
                        $accountName = $item?->account?->nama ?? 'Kategori Tidak Diketahui';
                
                        // Kirim notifikasi
                        Notification::make()
                            ->title('Status Lunas')
                            ->body("Pembayaran transaksi berhasil untuk **{$penerima} - {$accountName}**.")
                            ->success()
                            ->send();

                        // // **2. Notifikasi untuk Admin (Masuk ke lonceng/Database)**
                        // $admins = User::role('super_admin')->get();
                        // foreach ($admins as $admin) {
                        //     Notification::make()
                        //         ->title('Status Lunas')
                        //         ->body("**{$penerima} - {$accountName}** Pembayaran transaksi Bank berhasil diperbarui menjadi \"Sudah Dikirim\".")
                        //         ->success()
                        //         ->sendToDatabase($admin);
                        // }
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->transaction && $record->transaction->status === 'pending'),

                Tables\Actions\Action::make('rejectTransaction')
                    ->label('Transaksi Ditolak')
                    ->icon('heroicon-o-x-circle')
                    ->action(function ($record) {
                        if ($record->transaction) {
                            $record->transaction->update(['status' => 'unpaid']);
                        }
                        $record->update(['lunas' => false]);
                
                        // Ambil data transaksi
                        $User = $record->user->name ?? 'Admin Tidak Diketahui';
                        $penerima = $record->transaction->penerima ?? 'Penerima Tidak Diketahui';
                        $item = $record->transaction->items()->first();
                        $accountName = $item?->account?->nama ?? 'Kategori Tidak Diketahui';
                
                        // **1. Notifikasi Popup (Toast)**
                        Notification::make()
                            ->title('Transaksi Ditolak')
                            ->body("Transaksi **{$penerima} - {$accountName}** telah ditolak oleh **{$User}**.")
                            ->danger()
                            ->send();
                
                        // // **2. Notifikasi untuk Admin (Lonceng/Database)**
                        // $admins = User::role('super_admin')->get();
                        // foreach ($admins as $admin) {
                        //     Notification::make()
                        //         ->title('Transaksi Ditolak')
                        //         ->body("**{$penerima} - {$accountName}** Transaksi telah ditolak oleh {$User}.")
                        //         ->danger()
                        //         ->sendToDatabase($admin);
                        // }
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->transaction && $record->transaction->status === 'pending'),
                
                Tables\Actions\Action::make('markAsSent')
                    ->label('Tandai Sudah Dikirim')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($record) {
                        $record->markAsSent();
                
                        // Ambil data transaksi
                        $penerima = $record->penerima ?? 'Penerima Tidak Diketahui';
                        $subtotal = number_format($record->subtotal, 2, ',', '.'); // Format jumlah saldo
                        $accountName = $record->account?->nama ?? 'Kategori Tidak Diketahui';
                
                        // **1. Notifikasi Popup (Toast)**
                        Notification::make()
                            ->title('Status Dikirim')
                            ->body("Saldo sebesar Rp{$subtotal} telah berhasil dikirim kepada **{$penerima} - {$accountName}**.")
                            ->success()
                            ->send();
                
                        // // **2. Notifikasi untuk Admin (Lonceng/Database)**
                        // $admins = User::role('super_admin')->get();
                        // foreach ($admins as $admin) {
                        //     Notification::make()
                        //         ->title('Status Dikirim')
                        //         ->body("**{$penerima} - {$accountName}** Saldo sebesar Rp{$subtotal} telah berhasil dikirim.")
                        //         ->success()
                        //         ->sendToDatabase($admin);
                        // }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (rekeningsaldo $record) => $record->status === 'pending'),
                
                // Tables\Actions\Action::make('markAsPending')
                //     ->label('Tandai Belum Lunas')
                //     ->icon('heroicon-o-x-circle')
                //     ->action(function ($record) {
                //         // Perbarui status transaksi jika ada hubungan dengan transaksi
                //         if ($record->transaction) {
                //             $record->transaction->update(['status' => 'pending']);
                //         }
                        
                //         // Tetap perbarui status `lunas`
                //         $record->update(['lunas' => false]);
                
                //         // Kirim notifikasi
                //         Notification::make()
                //             ->title('Status Belum Lunas')
                //             ->body('Transaksi ditandai sebagai belum terbayar.')
                //             ->warning()
                //             ->send()
                //             ->sendToDatabase(User::whereHas('roles', function ($query) {
                //                 $query->where('name', 'admin');
                //             })->get());
                //     })
                //     ->requiresConfirmation()
                //     ->visible(fn ($record) => $record->transaction && $record->transaction->status === 'paid')
                    
            ]),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                BulkAction::make('print_selected')
                ->label('Laporan Arus Kas Besar')
                ->icon('heroicon-o-printer')
                ->action(function (Collection $records) {
                    // Simpan ID yang dipilih ke sesi
                    session(['selected_ids' => $records->pluck('id')->toArray()]);
                    
                    // Redirect ke route cetak dengan parameter selected
                    return redirect()->route('laporan.rekening', ['selected' => true]);
                })
                ->requiresConfirmation(),
                // ExportBulkAction::make(),
                Tables\Actions\DeleteBulkAction::make(),
            ])->label('Setting'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRekeningSaldos::route('/'),
            'create' => Pages\CreateRekeningSaldo::route('/create'),
            'edit' => Pages\EditRekeningSaldo::route('/{record}/edit'),
        ];
    }
}
