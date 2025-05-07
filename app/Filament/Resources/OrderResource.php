<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use Filament\Resources\Resource;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\OrderResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-m-shopping-cart';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Main Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('gender')
                            ->required()
                            ->native(false)
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female'
                            ]),
                    ]),
                ]),
                Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->placeholder('Optional')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->numeric()
                            ->placeholder('Optional')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('birthday')
                            ->displayFormat('d F Y')
                            ->weekStartsOnMonday()
                            ->locale('id')
                            ->placeholder('Optional')
                            ->native(false)
                            ->columnSpanFull(),
                    ])->columns(2)
                ]),
                Forms\Components\Section::make('Ordered Products')
                    ->schema([
                        self::getItemsRepeater()
                    ]),
                Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Select::make('payment_method_id')                        
                            ->relationship('paymentMethod', 'name')
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function($state, Forms\Set $set, Forms\Get $get)
                            {
                                $paymentMethod = PaymentMethod::find($state);
                                $set('is_cash', $paymentMethod?->is_cash ?? false);

                                if(!$paymentMethod?->is_cash)
                                {
                                    $set('change_amount', 0);
                                    $set('paid_amount', $get('total_price'));
                                }
                            })
                            ->afterStateHydrated(function(Forms\Set $set, Forms\Get $get, $state)
                            {
                                $paymentMethod = PaymentMethod::find($state);
                                if(!$paymentMethod?->is_cash)
                                {
                                    $set('paid_amount',$get('total_price'));
                                    $set('change_amount', 0);
                                }
                                $set('is_cash', $paymentMethod?->is_cash ?? false);
                            }),
                        Forms\Components\Hidden::make('is_cash')
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('paid_amount')
                            ->numeric()
                            ->reactive()
                            ->debounce(1000)
                            ->readOnly(fn(Forms\Get $get) => $get('is_cash') == false)
                            ->afterStateUpdated(function(Forms\Set $set, Forms\Get $get, $state)
                            {
                                self::updateExchangePaid($set, $get);
                            }),
                        Forms\Components\TextInput::make('change_amount')
                            ->numeric()
                            ->readOnly(),
                    ])
                ]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\TextInput::make('total_price')
                            ->required()
                            ->readOnly()
                            ->numeric(),
                            Forms\Components\Textarea::make('note')
                            ->columnSpanFull(),
                        ])
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make(('gender')),
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->badge()
                    ->color('success')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('change_amount')
                    ->numeric()
                    ->sortable(),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getItemsRepeater():Repeater
    {
        return Forms\Components\Repeater::make('orderProducts')
        ->relationship('orderProducts')
        ->live()
        ->columns([
            'md' => 10,
        ])
        ->afterStateUpdated(function(Forms\Set $set, Forms\Get $get)
        {
            self::updateTotalPrice($set, $get);
        })
        ->schema([
            Forms\Components\Select::make('product_id')
                ->label('Product')
                ->required()
                ->options(Product::where('stock', '>', 1)->pluck('name', 'id'))
                ->native(false)
                ->searchable()
                ->columnSpan([
                    'md' => 5,
                ])
                ->afterStateHydrated(function(Forms\Set $set, Forms\Get $get, $state)
                {
                    $product = Product::find($state);
                    $set('unit_price', $product->price ?? 0);
                    $set('stock', $product->stock ?? 0);                    
                })
                ->afterStateUpdated(function($state, Forms\Set $set, Forms\Get $get)
                {
                    $product = Product::find($state);
                    $set('unit_price', $product->price ?? 0);
                    $set('stock', $product->stock ?? 0);
                    self::updateTotalPrice($set, $get);
                })
                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
            Forms\Components\TextInput::make('quantity')
                ->required()
                ->numeric()
                ->minValue(0)
                ->default(1)
                ->columnSpan([
                    'md' => 1,
                ])
                ->afterStateUpdated(function($state, Forms\Set $set, Forms\Get $get)
                {
                    $stock = $get('stock'); 
                    if($state > $stock)
                    {
                        $set('quantity', $stock); // set quantity as same as maximum stock value
                        Notification::make()
                        ->title('Quantity exceeds stock!')
                        ->warning()
                        ->send();
                    }
                    self::updateTotalPrice($set, $get);
                }),
            Forms\Components\TextInput::make('stock')
                ->required()
                ->numeric()
                ->disabled()
                ->columnSpan([
                    'md' => 1,
                ]),
            Forms\Components\TextInput::make('unit_price')
                ->required()
                ->numeric()
                ->minValue(0)
                ->readOnly()
                ->prefix('IDR')
                ->columnSpan([
                    'md' => 2,
                ]),
        ]);

    }

    protected static function updateTotalPrice(Forms\Set $set, Forms\Get $get)
    {
        // take values from repeater in a collection selectedProducts
        $selectedProducts = collect($get('orderProducts'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity'])); // nested collection
        
        // get the unit price based on product_id from selectedProducts collection     
        $prices = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');  // collection ['id' => 'price']

        // count the prices for each product in selectedProducts array
        $total = $selectedProducts->reduce(function($total, $itemFromSelectedProducts) use($prices)
        {
            return $total + ($prices[$itemFromSelectedProducts['product_id']] * $itemFromSelectedProducts['quantity']);
         },0);

        $set('total_price', $total);
    }

    protected static function updateExchangePaid(Forms\Set $set, Forms\Get $get)
    {
        $paidAmount = (int) $get('paid_amount') ?? 0;
        $totalPrice = (int) $get('total_price') ?? 0;
        $exchangePaid = $paidAmount - $totalPrice;
        $set('change_amount', $exchangePaid);
    }
}
