<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    } 
    
    public function penerima()
    {
    return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    
    public function kontak()
    {
        return $this->belongsTo(Kontak::class);
    }

    public function saldo()
    {
        return $this->hasOne(Saldo::class);
    }
    
    public function rekeningsaldo()
    {
        return $this->hasOne(Rekeningsaldo::class);
    }

    public function asset_items()
    {
        return $this->hasMany(AssetItem::class);
    }


    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        // Generate UUID setiap kali membuat model baru
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
        
        static::addGlobalScope('latestFirst', function ($query) {
            $query->orderBy('created_at', 'desc');
        });

        static::creating(function ($model) {
            // Jika kolom nomor belum terisi, maka kita generate kode bank
            if (empty($model->nomor_trx)) {
                $model->nomor_trx = $model->generateNomorTRX();
            }
        });
    }

    public function generateNomorTRX()
    {
    // Format kode: BANKYYYYMMDDXXX (BANK + TahunBulanTanggal + NomorUrut)
    $date = now()->format('Ymd');
    
    // Pastikan kita menyebutkan tabel secara eksplisit
    $latestTransaksi = DB::table($this->getTable())  // Menggunakan $this->getTable() untuk mendapatkan nama tabel dari model
        ->whereDate('created_at', now()->format('Y-m-d'))  // Pastikan hanya mengambil transaksi di hari ini
        ->latest('id')  // Ambil transaksi terakhir berdasarkan ID
        ->first();
    
    // Jika ada transaksi terakhir, ambil nomor urutnya, jika tidak mulai dari 0
    $lastNumber = $latestTransaksi ? intval(substr($latestTransaksi->nomor_trx, -3)) : 0;
    $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);  // Nomor urut yang akan digunakan

    return 'TRX-' . $date . '/' . $nextNumber;
    }
}
