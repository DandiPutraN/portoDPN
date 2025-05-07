<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\PaymentMethodObserver;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([PaymentMethodObserver::class])]
class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'image',
        'is_cash'
    ];
    
    protected $appends = ['image_url'];
    
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? url('storage/' . $this->image) : null;
    }
}
