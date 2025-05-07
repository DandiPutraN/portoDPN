<?php

namespace App\Http\Controllers;

use App\Models\Saldo;
use App\Models\transaction;
use Illuminate\Http\Request;

class SaldoController extends Controller
{
    public function print($id)
    {
        $transaction = Saldo::with('saldo_items', 'kontak')->findOrFail($id); 
        return view('saldo.print', compact('transaction')); 
    }

    public function printVoucher($id)
    {
        $transaction = Saldo::with('saldo_items', 'kontak')->findOrFail($id); 
        return view('saldo.voucher', compact('transaction')); 
    }

    // public function printVoucher($id)
    // {
    //     $transaction = transaction::with('items')->findOrFail($id); // Mengambil transaksi berdasarkan ID dan relasi item
    //     return view('saldo.voucher', compact('transaction')); // Mengirim data transaksi ke view
    // }
    
}
