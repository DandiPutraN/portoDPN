<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function print($id)
    {
        $transaction = Transaction::with('items')->findOrFail($id); // Mengambil transaksi berdasarkan ID dan relasi item
        return view('transaction.print', compact('transaction')); // Mengirim data transaksi ke view
    }
}
