<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function saldoItems()
    {
        return $this->hasMany(SaldoItem::class, 'saldo_id');
    }

    public function rekeningsaldoItems()
    {
        return $this->hasMany(Rekeningsaldoitem::class); //  , 'rekening_saldo_id'
    }

    public function asset_items()
    {
        return $this->hasMany(AssetItem::class);
    }
}
