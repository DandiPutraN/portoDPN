<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Forms\Components\BelongsToSelect;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'E-Journal';

    protected static ?string $navigationGroup = 'Transaction';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Transaction')->schema([
                        Select::make('bayar_dari')
                        ->label('Pembayaran')
                        ->required()
                        ->default('1-00001 - Kas')
                        ->options([
                            '1-00001 - Kas' => '1-00001 - Kas',
                            '1-00002 - Rekening' => '1-00002 - Rekening',
                            '1-00003 - Giro' => '1-00003 - Giro',
                        ]),
                        
                        DatePicker::make('tanggal_transaksi')
                        ->default(now())
                        ->label('Tanggal')
                        ->required(),

                        TextInput::make('penerima')
                        ->required(),

                        Select::make('divisi')
                        ->label('Divisi (optional)')
                        ->searchable()
                        ->preload()
                        ->options([
                            'IT' => 'Information Technology',
                            'DGMT' => 'Digital Marketing',
                            'MARKETING' => 'Marketing',
                            'LEGAL' => 'Legal',
                            'BND' => 'Bussines and Development',
                            'RND' => 'Research and Development',
                            'QC' => 'Quality Control',
                            'Desain Grafis' => 'Desain Grafis',
                            'Engineering' => 'Engineering',
                            'Purchasing' => 'Purchasing',
                            'Finance' => 'Finance',
                            'Apoteker' => 'Apoteker',
                            'Warehouse' => 'Warehouse',
                            'Produksi' => 'Produksi',
                            'GA' => 'General Affair',
                            'None' => 'none',
                        ])
                        ->default('None'),
                        
                        DatePicker::make('jatuh_tempo')
                        ->label('Jatuh Tempo')
                        ->visible(fn ($get) => $get('lunas')) // Tampilkan jika toggle 'lunas' aktif
                        ->required(fn ($get) => $get('lunas') === true), // Tampilkan dan set wajib saat "Bayar Nanti" aktif

                        Select::make('termin')
                        ->visible(fn ($get) => $get('lunas')) // Tampilkan jika toggle 'lunas' aktif
                        ->required(fn ($get) => $get('lunas')) // Set wajib diisi jika toggle 'lunas' aktif
                        ->placeholder('Hari/tanggal jatuh tempo')
                        ->default('Custom')
                        ->options([
                            'Net 15' => 'Net 15',
                            'Net 30' => 'Net 30',
                            'Net 60' => 'Net 60',
                            'Custom' => 'Custom',
                        ])

                    ])->columns(2),
                ])->columnSpan(5),

                Group::make()->schema([
                    Section::make('Status')->schema([
                        Toggle::make('lunas')
                        ->label('Bayar Nanti')
                        ->default(false)
                        ->reactive(), // Mengaktifkan reactivity untuk memantau perubahan nilai

                        Hidden::make('status')
                        ->default('Pending')

                        // ToggleButtons::make('status')
                        // ->label('')
                        // ->inline()
                        // ->disabled()
                        // ->default('pending')
                        // ->options([
                        //     'Pending' => 'Pending',
                        //     'Paid' => 'Paid',
                        //     'Unpaid' => 'Unpaid',
                        // ])
                        // ->colors([
                        //     'pending' => 'warning',
                        //     'paid' => 'success',
                        //     'unpaid' => 'danger',
                        // ])
                        // ->icons([
                        //     'pending' => 'heroicon-m-arrow-path',
                        //     'paid' => 'heroicon-m-check-badge',
                        //     'unpaid' =>  'heroicon-m-x-circle',
                        // ]),
                    ]),
                ])->columnSpan(1),

                Group::make()->schema([
                    Section::make('Detail items')->schema([
                        Repeater::make('items')
                        ->label('')
                        ->relationship()
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
                            ->columnSpan(4),                                              
    
                            TextInput::make('biaya')
                            ->required()
                            ->numeric()
                            ->debounce(1000)
                            ->columnSpan(2)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Update total biaya ketika biaya berubah
                                $subtotal = collect($get('items'))
                                    ->sum(fn($item) => $item['biaya'] ?? 0);
                        
                                $set('subtotal', $subtotal);
                            }),
                            
                            TextInput::make('keterangan')
                            ->columnSpan(6),
                            
                            ])->columns(12),  
                        
                            Section::make()->schema([
                                Placeholder::make('subtotal_placeholder')
                                ->label('Subtotal')
                                ->content(function (Get $get, Set $set) {
                                    $total = 0;
                            
                                    if (!$repeaters = $get('items')) {
                                        $set('terbilang', 'Nol Rupiah'); // Mengatur terbilang jika tidak ada item
                                        return Number::currency($total, 'IDR'); // Mengembalikan 0 jika tidak ada item
                                    }
                            
                                    foreach ($repeaters as $key => $repeater) {
                                        // Pastikan nilainya menjadi integer atau float sebelum dijumlahkan
                                        $amount = (float) $get("items.{$key}.biaya");
                                        $total += $amount;
                                    }
                            
                                    // Set subtotal dengan nilai total
                                    $set('subtotal', $total);
                            
                                    // Mengonversi total menjadi teks terbilang
                                    $terbilang = self::convertToWords($total) . ' Rupiah';
                                    
                                    // Mengatur field terbilang dengan hasil konversi
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
                            ])->columns(2),
                        
                        Hidden::make('user_id'),

                        Hidden::make('subtotal'),
                        
                        Hidden::make('terbilang'),
                    ])
                ])->columnSpanFull(),
            ])->columns(6);
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
                IconColumn::make('status_icon') // Gunakan nama yang berbeda untuk menghindari konflik
                ->label('Pay')
                ->options([
                    'heroicon-o-clock' => 'pending',       // Ikon untuk status pending
                    'heroicon-o-check-circle' => 'paid',   // Ikon untuk status paid
                    'heroicon-o-x-circle' => 'unpaid',     // Ikon untuk status unpaid
                ])
                ->colors([
                    'warning' => fn ($state) => $state === 'pending', // Warna kuning untuk pending
                    'success' => fn ($state) => $state === 'paid',    // Warna hijau untuk paid
                    'danger' => fn ($state) => $state === 'unpaid',   // Warna merah untuk unpaid
                ])
                ->getStateUsing(fn ($record) => $record->status), // Sinkronkan ikon dengan status,
            
            // SelectColumn::make('status')
            //     ->options([
            //         'pending' => 'Pending',
            //         'approved' => 'Approved',
            //         'canceled' => 'Canceled',
            //     ])
            //     ->searchable()
            //     ->sortable()
            //     ->toggleable(isToggledHiddenByDefault: true)
            //     ->afterStateUpdated(function ($state, $record) {
            //         // Update status di database saat status diubah melalui SelectColumn
            //         $record->update(['status' => $state]);
            //     }),            

                TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->copyable()
                    ->copyableState(fn (string $state): string => "{$state}")
                    ->sortable(),
                    
                TextColumn::make('nomor_trx')
                ->label('No. Transaksi')
                ->sortable()
                ->searchable(),
                    
                TextColumn::make('bayar_dari')
                ->label('Pembayaran')
                ->searchable()
                ->sortable()
                ->badge()
                ->colors([
                    'primary' => '1-00001 - Kas',
                    'success' => '1-00002 - Rekening',
                    'info' => '1-00003 - Giro',
                ])
                ->formatStateUsing(function ($state) {
                    return preg_replace('/^\d+-\d+ - /', '', $state);
                }),
            
                
                TextColumn::make('penerima')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('items')
                ->label('Keterangan')
                ->formatStateUsing(function ($record) {
                    // Mengambil data saldo_items dan hanya menampilkan nama kategori
                    $accounts = $record->items->map(function ($item) {
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
                    return $record->items->map(function ($item) {
                        return $item->account->nama; // Menampilkan seluruh nama kategori
                    })->join(' | '); // Gabungkan hasilnya
                })
                ->sortable(),                      

                TextColumn::make('subtotal')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(Sum::make()),

                TextColumn::make('lunas')
                ->label('Bayar Nanti')
                ->badge()
                ->searchable()
                ->color(fn (?int $state): string => $state === 0 ? 'success' : 'danger')
                ->formatStateUsing(fn (?int $state): string => $state === 0 ? 'Lunas' : 'Belum Lunas'),

                // TextColumn::make('sisa_tagihan')
                // ->label('Sisa Tagihan')
                // ->money('IDR')
                // ->sortable(),

                TextColumn::make('jatuh_tempo')
                ->label('Tgl jatuh tempo')
                ->date('d/F/Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                SelectFilter::make('status')
                ->options([
                    'Pending' => 'Pending',
                    'Paid' => 'Paid',
                    'Unpaid' => 'Unpaid',
                ]),

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
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('print')
                        ->label('Cetak Pengajuan')
                        ->icon('heroicon-o-printer')
                        ->action(function (Transaction $record) {
                            return redirect()->route('transaction.print', $record->id);
                        }),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
