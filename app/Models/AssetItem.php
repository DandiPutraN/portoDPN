<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class AssetItem extends Model
{
    // /** @return MorphTo<Model,self> */
    // public function assetable(): MorphTo
    // {
    //     return $this->morphTo();
    // }

    protected $casts = [
        'account_id' => 'float',
        'items' => 'array',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
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
    }
}
