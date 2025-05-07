<?php

namespace App\Filament\Resources\AssetResource\Pages;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\AssetResource;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;

class TransactionAsset extends ManageRelatedRecords
{
    protected static string $resource = AssetResource::class;

    protected static string $relationship = 'items';

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    public function getBreadcrumb(): string
    {
        return 'items';
    }

    public static function getNavigationLabel(): string
    {
        return 'Transaction Asset';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema([
                    Grid::make(2)->schema([ // Membuat tampilan lebih luas
                        Select::make('transaction_id')
                        ->searchable()
                        ->preload()
                        ->placeholder('select an option / kosongkan apabila tidak ada')
                        ->label('Nomor Transaksi')
                        ->relationship('transaction', 'nomor_trx', function ($query) {
                            $query->whereHas('items', function ($q) {
                                $q->where('account_id', 120);
                            });
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                // Ambil transactionitems berdasarkan transaction_id yang dipilih
                                $transactionItems = \App\Models\TransactionItem::where('transaction_id', $state)->get();
                    
                                $assetItems = $transactionItems->map(fn ($item) => [
                                    'account_id' => $item->account_id,
                                    'debit' => 0,
                                    'kredit' => $item->biaya,
                                ])->toArray();
                    
                                // Set nilai asset_items
                                $set('items', $assetItems);
                    
                                // Ambil nomor transaksi
                                $transaction = \App\Models\Transaction::find($state);
                                if ($transaction) {
                                    $set('nomor_trx', $transaction->nomor_trx);
                                }
                            } else {
                                $set('items', []);
                                $set('nomor_trx', null);
                            }
                        })
                        ->reactive(),

                        DatePicker::make('tgl')
                            ->label('Tanggal')
                            ->default(now())
                            ->required(),

                        Hidden::make('user_id')
                        ->default(1),
                    ]),
                ]),

                Section::make()->schema([
                    Grid::make(5)->schema([
                        Repeater::make('items')
                            ->label('Detail Aset')
                            ->schema([
                                Grid::make(5)->schema([
                                    Select::make('account_id')
                                        ->relationship('account', 'nama')
                                        ->label('Kategori Aset')
                                        ->columnSpan(3), // Menggunakan 3 kolom dari Grid 5
                                        // ->disabled(),
                
                                    TextInput::make('kredit')
                                        ->label('Biaya (Kredit)')
                                        ->numeric()
                                        ->columnSpan(2), // Menggunakan 2 kolom dari Grid 5
                                        // ->disabled(),
                                ]),
                            ])
                            ->columnSpanFull() // Menyesuaikan dengan Grid parent
                            // ->hidden(fn ($get) => empty($get('items'))) // Sembunyikan jika tidak ada data
                            ->disableItemCreation() // Mencegah user menambah item manual
                            ->disableItemDeletion(),
                    ]),
                ]),
                
            ])
            ->columns(1);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                TextEntry::make('transaction.nomor_trx'),
                TextEntry::make('tgl')
                ->date('d/m/Y'),
                // IconEntry::make('is_visible')
                //     ->label('Visibility'),
                TextEntry::make('account.nama'),
                TextEntry::make('debit')
                ->money('IDR'),
                TextEntry::make('kredit')
                ->money('IDR'),
                    // ->markdown(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tgl')
            ->columns([
                Tables\Columns\TextColumn::make('tgl')
                    ->label('Tanggal')
                    ->searchable()
                    ->sortable()
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('account.nama')
                    ->label('Reference')
                    ->searchable()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('debit')
                    ->money('IDR')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('kredit')
                    ->money('IDR')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('debit')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(Sum::make()),
                
                Tables\Columns\TextColumn::make('kredit')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(Sum::make()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->groupedBulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}