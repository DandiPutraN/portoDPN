<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Saldo extends Model
{
    public function saldo_items()
    {
        return $this->hasMany(SaldoItem::class);
    }
    
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function kontak()
    {
        return $this->belongsTo(Kontak::class, 'kontak_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }  

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setAttributesForInsert(array &$attributes)
    {
        // Hapus saldo_items dari attributes yang akan disimpan
        unset($attributes['saldo_items']);
        return $attributes;
    }

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

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
            if (empty($model->nomor)) {
                $model->nomor = $model->generateKodeBank();
            }
        });
    }
    
    public function generateKodeBank()
    {
    // Format kode: BANKYYYYMMDDXXX (BANK + TahunBulanTanggal + NomorUrut)
    $date = now()->format('Ymd');
    
    // Pastikan kita menyebutkan tabel secara eksplisit
    $latestTransaksi = DB::table($this->getTable())  // Menggunakan $this->getTable() untuk mendapatkan nama tabel dari model
        ->whereDate('created_at', now()->format('Y-m-d'))  // Pastikan hanya mengambil transaksi di hari ini
        ->latest('id')  // Ambil transaksi terakhir berdasarkan ID
        ->first();
    
    // Jika ada transaksi terakhir, ambil nomor urutnya, jika tidak mulai dari 0
    $lastNumber = $latestTransaksi ? intval(substr($latestTransaksi->nomor, -3)) : 0;
    $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);  // Nomor urut yang akan digunakan

    return 'KAS/' . $date . '/' . $nextNumber;
    }
}
