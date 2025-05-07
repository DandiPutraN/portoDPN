<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;

class OrderController extends Controller
{
    public function index(){
        $orders = Order::with('paymentMethod', 'orderProducts')->get();

        $orders->transform(function ($order){
            $order->payment_method = $order->paymentMethod->name ?? '-';
            $order->orderProducts->transform(function ($item){
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? '-',
                    'quantity' => $item->quantity ?? 0,
                    'unit_price' => $item->unit_price ?? 0
                ];
            });

            return $order;
        });
        return response()->json([
            'success' => true,
            'message' => 'Successfully get ' . $orders->count() . ' data',
            'data' => $orders
        ]);
    }

    public function store(StoreOrderRequest $request){
       $data = $request->validated();

       foreach($data['items'] as $item){
            $product = Product::find($item['product_id']);
            if(!$product || $product->stock < $item['quantity'])
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock of the product : ' . $product->name . ' is empty'
                ], 422);
            }
       }

       $order = Order::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'gender' => $data['gender'],
            'birthday' => $data['birthday'],
            'phone' => $data['phone'],
            'total_price' => $data['total_price'],
            'note' => $data['note'],
            'payment_method_id' => $data['payment_method_id'],
       ]);

       foreach($request->items as $item)
       {
        $order->orderProducts()->create([
            'product_id'=> $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price']
        ]); 
       }

       return response()->json([
            'success' => true,
            'message' => 'Successfully added an order data',
            'data' => $order
       ], 200);

    }
}
