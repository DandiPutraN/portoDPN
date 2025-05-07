<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductImport implements ToModel, WithHeadingRow, WithMultipleSheets, SkipsEmptyRows
{
    public function sheets():array
    {
        return [
            0 => $this
        ];
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        
        return new Product([
            'name' => $row['name'],
            'slug' => Product::generateUniqueSlug($row['name']),
            'category_id' => $row['category_id'],
            'stock' => $row['stock'],
            'price' => $row['price'],
            'is_active' => $row['is_active'],
            'barcode' => $row['barcode'],
            'image' => $row['image']
        ]);
    }

    // public function rules():array
    // {
    //     return [
    //         '*.name' => ['required', 'string'],
    //         '*.category_id' => ['required', 'exists:categories,id'],
    //         '*.stock' => ['required', 'integer', 'min:0'],
    //         '*.price' => ['required', 'numeric', 'min:0'],
    //         '*.barcode' => ['required', 'string', 'unique: products, barcode'],        
    //         '*.image' => ['nullable', 'string', 'max:255'],        
    //     ];
    // }

    // public function customValidationMessages()
    // {

    // }
}
