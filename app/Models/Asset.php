<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction_items()
    {
        return $this->belongsTo(Transactionitem::class);
    }

    // // /** @return MorphMany<asset_items> */
    // public function asset_items(): MorphMany
    // {
    //     return $this->morphMany(AssetItems::class, 'assetable');
    // }

    public function items()
    {
        return $this->hasMany(AssetItem::class);
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
            if (empty($model->nomor)) {
                $model->nomor = $model->generateNomorAset();
            }
        });

        static::creating(function ($asset) {
            $asset->nilai_buku = $asset->harga_beli;
        });

    }



    public function hitungPenyusutanTahunan()
    {
        if (!is_null($this->masa_manfaat) && $this->masa_manfaat > 0) {
            // Metode Garis Lurus
            return ($this->harga_beli - $this->nilai_residu) / $this->masa_manfaat;
        } elseif (!is_null($this->presentase_penyusutan) && $this->presentase_penyusutan > 0) {
            // Metode Saldo Menurun
            return $this->nilai_buku * ($this->presentase_penyusutan / 100);
        }
        return 0; // Tidak ada penyusutan jika parameter tidak diisi
    }

    /**
     * Update nilai buku setelah penyusutan tahunan.
     */
    public function updateNilaiBuku()
    {
        $penyusutan = $this->hitungPenyusutanTahunan();

        if ($this->nilai_buku > $this->nilai_residu) {
            // Pastikan nilai buku tidak turun di bawah nilai residu
            $this->nilai_buku = max($this->nilai_residu, $this->nilai_buku - $penyusutan);
            $this->save();
        }
    }

    public function generateNomorAset()
    {
        do {
            // Ambil transaksi terakhir berdasarkan nomor urut
            $latestAsset = DB::table($this->getTable())
                ->where('nomor', 'LIKE', 'AST-%') // Hanya ambil nomor dengan format AST-XXX
                ->orderBy('id', 'desc') // Ambil berdasarkan ID terbaru
                ->first();
    
            // Jika ada transaksi, ambil nomor urut terakhir, jika tidak mulai dari 001
            $lastNumber = $latestAsset ? intval(substr($latestAsset->nomor, 4)) : 0;
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    
            $nomor = 'AST-' . $nextNumber;
    
            // Cek apakah nomor ini sudah ada di database
            $exists = DB::table($this->getTable())->where('nomor', $nomor)->exists();
        } while ($exists); // Ulangi jika nomor sudah ada
    
        return $nomor;
    }
}
