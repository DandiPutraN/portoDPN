<?php

namespace App\Filament\Clusters\Products\Resources;

use App\Filament\Clusters\Products;
use App\Filament\Clusters\Products\Resources\KontakResource\Pages;
use App\Filament\Clusters\Products\Resources\KontakResource\RelationManagers;
use App\Models\Kontak;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KontakResource extends Resource
{
    protected static ?string $model = Kontak::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Kontak';
    
    protected static ?string $pluralModelLabel = 'Kontak';

    protected static ?string $cluster = Products::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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

                        Textarea::make('alamat')
                        ->required()
                        ->columnSpanFull()
        
                        ])->columns(2) 
                ])->columnSpan(5),

                Group::make()->schema([
                   Section::make('Kategori')->schema([
                        ToggleButtons::make('kategori')
                        ->inline()
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
                    ->required(fn ($get) => $get('kategori') === 'Lainnya')
                    ->visible(fn ($get) => $get('kategori') === 'Lainnya'),
                        
                   ]) 
                ])->columnSpan(1),
            ])->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                ->sortable()
                ->searchable(),

                TextColumn::make('perusahaan')
                ->sortable()
                ->searchable(),

                TextColumn::make('kategori')
                ->badge()
                ->colors([
                    'warning' => 'vendor',
                    'success' => 'pegawai',
                    'info' => 'lainnya',
                ]),

                // TextColumn::make('alamat')
                // ->sortable()
                // ->searchable(),

                TextColumn::make('telepon')
                ->sortable()
                ->searchable(),
            ])

            ->filters([
                //
            ])

            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    ]),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->label('Setting'),
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
            'index' => Pages\ListKontaks::route('/'),
            'create' => Pages\CreateKontak::route('/create'),
            'edit' => Pages\EditKontak::route('/{record}/edit'),
        ];
    }
}
