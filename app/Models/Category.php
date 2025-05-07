<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{ 
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public static function generateUniqueSlug(string $name):string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;
        while(self::where('slug', $slug)->exists())
        {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

}
