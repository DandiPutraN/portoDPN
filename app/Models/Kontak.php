<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kontak extends Model
{
    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

    public function saldo()
    {
        return $this->hasMany(Saldo::class, 'kontak_id');
    }

    public function rekeningsaldo()
    {
        return $this->hasMany(Rekeningsaldo::class);
    }
}
