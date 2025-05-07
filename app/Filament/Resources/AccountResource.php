<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Account;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AccountResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AccountResource\RelationManagers;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Akun';

    protected static ?string $navigationGroup = 'Master';

    protected static ?string $recordTitleAttribute = 'nama_kategori';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Group::make()->schema([
                Section::make()->schema([
                    TextInput::make('nama')
                    ->label('Nama Akun')
                    ->required()
                    ->maxLength(255),
                    Select::make('kategori')
                    ->required()
                    ->searchable()
                    ->options([
                        'Kas & Bank' => 'Kas & Bank',
                        'Akun Piutang' =>'Akun Piutang',
                        'Persediaan' => 'Persediaan',
                        'Aktiva Lancar Lainnya' => 'Aktiva Lancar Lainnya',
                        'Aktiva Tetap' => 'Aktiva Tetap',
                        'Depresiasi & Amortisasi' => 'Depresiasi & Amortisasi',
                        'Depresiasi & Amortisasi' => 'Depresiasi & Amortisasi',
                        'Aktiva Lainnya' => 'Aktiva Lainnya',
                        'Akun Hutang' => 'Akun Hutang',
                        'Kewajiban Lancar Lainnya' => 'Kewajiban Lancar Lainnya',
                        'Kewajiban Jangka Panjang' => 'Kewajiban Jangka Panjang',
                        'Ekuitas' => 'Ekuitas',
                        'Pendapatan' => 'Pendapatan',
                        'Harga Pokok Penjualan' => 'Harga Pokok Penjualan',
                        'Beban' => 'Beban',
                        'Pendapatan Lainnya' => 'Pendapatan Lainnya',
                        'Beban Lainnya' => 'Beban Lainnya',
                    ]),
                ])
            ])->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                ->label('Kode Transaksi')
                ->sortable()
                ->searchable()
                ->formatStateUsing(function ($state) {
                    return '1-' . str_pad($state, 5, '0', STR_PAD_LEFT);
                }),                      
                TextColumn::make('nama')
                    ->label('Nama Akun')
                    ->searchable(),
                TextColumn::make('kategori')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])       
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
