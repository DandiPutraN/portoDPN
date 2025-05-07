<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Clusters\Products;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-s-building-storefront';
    
    protected static ?string $cluster = Products::class;

    protected static ?int $navigationSort = 1;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Product Detail')->schema([          
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->afterStateUpdated(function(Set $set, $state)
                            {
                                $set('slug', Product::generateUniqueSlug($state));
                            })
                            ->live(onBlur: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->readOnly()
                            ->maxLength(255),
                        Forms\Components\Select::make('category_id')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->relationship('category', 'name'),
                        Forms\Components\TextInput::make('stock')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(1),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('IDR'),
                        Forms\Components\TextInput::make('barcode')
                        ->maxLength(255),
                        Forms\Components\FileUpload::make('image')
                            ->required()
                            ->acceptedFileTypes(['image/png','image/jpg','image/jpeg'])
                            ->rules([ 'mimes:png,jpeg,jpg'])
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->imageEditorMode(2)
                            ->openable()
                            ->downloadable()
                            ->previewable(true)
                            ->minSize(10)
                            ->maxSize(2048)
                            ->disk('public')
                            ->directory('product-images')
                            ->visibility('public'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])->columns(2)
                ])->columnSpan(4),

                Group::make([
                    Section::make('Status')->schema([
                        Forms\Components\Toggle::make('is_active')
                        ->required(),                            
                    ])
                ])->columnSpan(1)
            ])->columns(5);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR',true)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),                
                Tables\Columns\TextColumn::make('barcode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('downloadGambar')
                ->label('Gambar')
                ->icon('heroicon-o-photo')
                ->url(fn ($record) => url('storage/' . strtoupper($record->image)))
                ->openUrlInNewTab()
                ->visible(fn ($record) => !empty($record->image)),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public function afterCreate(): void
    {
        $oldFiles = Storage::files('livewire-tmp');
        foreach ($oldFiles as $file) {
            Storage::delete($file);
        }
    }
}
