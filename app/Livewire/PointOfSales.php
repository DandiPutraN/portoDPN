<?php

namespace App\Livewire;

use Filament\Forms;
use App\Models\Order;

use App\Models\Product;
use Filament\Forms\Set;
use Livewire\Component;
use Filament\Forms\Form;

use App\Models\OrderProduct;
use Livewire\WithPagination;
use App\Models\PaymentMethod;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

class PointOfSales extends Component implements HasForms
{
    use WithPagination, InteractsWithForms;
    public $search = '';
    public $name_customer = '';
    public $payment_method_id = '';
    public $gender = '';
    public $payment_method;
    public $order_items = [];
    public $total_price;

    protected $listeners = [
        'scanResult' => 'handleScanResult'
    ];

    
    public function render()
    {
        return view('livewire.point-of-sales', [
            'products' => Product::where('stock', '>', 0)
                ->search($this->search)
                ->paginate(1)
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Form Checkout')
                ->schema([
                    Forms\Components\TextInput::make('name_customer')
                    ->required()
                    ->maxLength(255)
                    ->default(fn() => $this->name_customer),
                    Forms\Components\Select::make('gender')
                        ->options([
                            'Male' => 'Male',
                            'Female' => 'Female'
                            ])
                        ->native(false)
                        ->required(),
                    Forms\Components\TextInput::make('total_price')
                        ->readOnly()
                        ->numeric()
                        ->prefix('IDR')
                        ->default(fn() => $this->total_price),
                    Forms\Components\Select::make('payment_method_id')
                        ->label('Payment method')
                        ->options($this->payment_method->pluck('name', 'id'))
                        ->native(false)
                        ->required(),
                ])
            ]);
    }

    public function mount()
    {
        if(session()->has('X-ORDER-ITEMS'))
        {
            $this->order_items = session('X-ORDER-ITEMS');
        }
        $this->payment_method = PaymentMethod::all(); // collection
        // $this->form->fill(['payment_method', $this->payment_method]);
    }

    public function addToOrders($productId)
    {
        $product = Product::find($productId);

        if($product)
        {
            if($product->stock <= 0)
            {
                Notification::make()
                ->title('Stock is empty')
                ->danger()
                ->send();
                return;
            }

            // to make sure that one product does not add a cart with the same product over and over but just increase its quantity
            $existingItemKey = null;
            foreach($this->order_items as $key => $item)
            {
                if($item['product_id'] == $productId)
                {
                    $existingItemKey = $key;
                    break;
                }
            }

            if($existingItemKey !== null)
            {
                $this->order_items[$existingItemKey]['quantity']++; // increase the quantity of the product
            }else
            {
                $this->order_items[] = [
                    'product_id'=> $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image_url' => $product->image_url,
                    'quantity' => 1,
                ];
            }

            session()->put('X-ORDER-ITEMS', $this->order_items);
            Notification::make()
            ->title('Successfully added to cart')
            ->success()
            ->send();
        }
    }

    public function increaseQuantity($productId)
    {
        $product = Product::find($productId);

        if(!$product)
        {
            Notification::make()
                ->title('Product not found')
                ->danger()
                ->send();
            return;
        }

        foreach($this->order_items as $key => $item)
        {
            if($item['product_id'] == $productId)
            {
                if($item['quantity'] + 1 <= $product->stock)
                {
                    $this->order_items[$key]['quantity']++;
                }else
                {
                    Notification::make()
                    ->title('Out of stock')
                    ->danger()
                    ->send();
                }
                break;
            }
        }

        session()->put('X-ORDER-ITEMS', $this->order_items);
    }


    public function decreaseQuantity($productId)
    {
        foreach($this->order_items as $key => $item)
        {
            if($item['product_id'] == $productId)
            {
                if($this->order_items[$key]['quantity'] > 1)
                {
                    $this->order_items[$key]['quantity']--;
                }else
                {
                    unset($this->order_items[$key]);
                    $this->order_items = array_values($this->order_items);
                }
                break;
            }
        }

        session()->put('X-ORDER-ITEMS', $this->order_items);
    }

    public function calculateTotal()
    {
        $total = 0;
        foreach($this->order_items as $key => $item)
        {
            $total += $item['quantity'] * $item['price'];
        }

        $this->total_price = $total;
        return $total;
    }

    public function checkout()
    {
        $this->validate([
            'name_customer' => ['required', 'max:50'],
            'gender' => ['required', 'in:Male,Female'],
            'payment_method_id' => ['required']
        ]);

        $payment_method_id_temp = $this->payment_method_id;
        
        $order = Order::create([
            'name' => $this->name_customer,
            'gender' => $this->gender,
            'total_price' => $this->calculateTotal(),
            'payment_method_id' => $payment_method_id_temp,            
        ]);

        foreach($this->order_items as $item)
        {
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
            ]);
        }

        $this->order_items = [];
        session()->forget(['X-ORDER-ITEMS']);

        return redirect()->to('admin/orders');
        
    }

    public function handleScanResult($decodedText)
    {
        // find product based on its barcode from decodeText as a result from barcode scanner
        $product = Product::where('barcode', $decodedText)->first();

        if ($product) 
        {
            $this->addToOrder($product->id);
        } else 
        {
            Notification::make()
                ->title('Product not found with code: '.$decodedText)
                ->danger()
                ->send();
        }
    }
}
