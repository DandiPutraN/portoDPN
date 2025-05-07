<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\OrderProduct;

class OrderProductObserver
{
    /**
     * Handle the OrderProduct "created" event.
     */
    public function created(OrderProduct $orderProduct): void
    {
        $product = Product::find($orderProduct->product_id);
        $product->decrement('stock', $orderProduct->quantity);
    }
    
    /**
     * Handle the OrderProduct "updated" event.
     */
    public function updated(OrderProduct $orderProduct): void
    {
        $product = Product::find($orderProduct->product_id);
        $originalQuantityFromOrderProducts = $orderProduct->getOriginal('quantity');
        $newQuantityFromOrderProducts = $orderProduct->quantity;
        
        if($originalQuantityFromOrderProducts != $newQuantityFromOrderProducts)
        {
            // count the different
            $product->increment('stock', $originalQuantityFromOrderProducts);
            $product->decrement('stock', $newQuantityFromOrderProducts);
        }
        
    }

    /**
     * Handle the OrderProduct "deleted" event.
     */
    public function deleted(OrderProduct $orderProduct): void
    {
        $product = Product::find($orderProduct->product_id);
        $product->increment('stock', $orderProduct->quantity);
    }

    /**
     * Handle the OrderProduct "restored" event.
     */
    public function restored(OrderProduct $orderProduct): void
    {
        //
    }

    /**
     * Handle the OrderProduct "force deleted" event.
     */
    public function forceDeleted(OrderProduct $orderProduct): void
    {
        //
    }
}
