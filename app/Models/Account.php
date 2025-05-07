<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function saldo_items()
    {
        return $this->hasMany(SaldoItem::class);
    }

    public function rekeningsaldo_items()
    {
        return $this->hasMany(RekeningSaldoItem::class);
    }

    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

    public function saldo()
    {
        return $this->hasMany(Saldo::class);
    }

    public function rekeningsaldo()
    {
        return $this->hasMany(Rekeningsaldo::class);
    }

    public function asset()
    {
        return $this->hasMany(Asset::class);
    }

    public function asset_items()
    {
        return $this->hasMany(AssetItem::class);
    }
}
