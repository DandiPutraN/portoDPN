<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoItem extends Model
{
    public function saldo()
    {
        return $this->belongsTo(Saldo::class); 
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function items()
    {
        return $this->belongsTo(Transactionitem::class);
    }
}
