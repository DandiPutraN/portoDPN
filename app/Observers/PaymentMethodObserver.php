<?php

namespace App\Observers;

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Storage;

class PaymentMethodObserver
{
    /**
     * Handle the PaymentMethod "created" event.
     */
    public function created(PaymentMethod $paymentMethod): void
    {
        
    }

    /**
     * Handle the PaymentMethod "updated" event.
     */
    public function updated(PaymentMethod $paymentMethod): void
    {
        
        $oldImage = $paymentMethod->getOriginal('image');
        
        $newImage = $paymentMethod->image;
        
        if ($oldImage !== $newImage) 
        {
            if (Storage::exists($oldImage)) 
            {
                Storage::delete($oldImage);
            }
        }
    }

    /**
     * Handle the PaymentMethod "deleted" event.
     */
    public function deleted(PaymentMethod $paymentMethod): void
    {
        if(Storage::exists($paymentMethod->image))
        {
            Storage::delete( $paymentMethod->image);
        }
    }

    /**
     * Handle the PaymentMethod "restored" event.
     */
    public function restored(PaymentMethod $paymentMethod): void
    {
        //
    }

    /**
     * Handle the PaymentMethod "force deleted" event.
     */
    public function forceDeleted(PaymentMethod $paymentMethod): void
    {
        //
    }
}
