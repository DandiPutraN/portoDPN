<?php

namespace App\Filament\Resources\AssetResource\RelationManagers;

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
use App\Models\transactionitem;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class AssetItemRelationManager extends RelationManager
{
    protected static string $resource = AssetResource::class;

    protected static string $relationship = 'items';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

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
                        ->label('Nomor Transaksi')
                        ->relationship('transaction', 'nomor_trx')
                        ->required()
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
                        Select::make('account_id')
                            ->relationship('account', 'id', fn ($query) => $query->whereBetween('id', [100, 153]))
                            ->label('Akun Perubahan Nilai Aset')
                            ->searchable()
                            ->columnSpan(3)
                            ->required()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => '1-' . str_pad($record->id, 5, '0', STR_PAD_LEFT) . ' - ' . $record->nama),

                        TextInput::make('kredit')->numeric()
                        ->columnSpan(2),
                        // TextInput::make('kredit')->numeric(),
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
            ->recordTitleAttribute('Transaksi Asset')
            ->columns([
                Tables\Columns\TextColumn::make('tgl')
                    ->label('Tanggal')
                    ->sortable()
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('account.nama')
                    ->label('Reference')
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('debit')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('kredit')
                    ->money('IDR')
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

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                TransactionItem::create([
                    'transaction_id' => $data['transaction_id'],
                    'account_id' => $item['account_id'],
                    'biaya' => $item['kredit'],
                ]);
            }
        }
    
        unset($data['items']); // Hapus 'items' agar tidak masuk ke tabel asset_items
    
        return $data;
    }
    
}
