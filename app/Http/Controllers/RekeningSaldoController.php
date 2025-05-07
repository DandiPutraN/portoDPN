<?php

namespace App\Http\Controllers;

use App\Models\RekeningSaldo;
use Illuminate\Http\Request;

class RekeningSaldoController extends Controller
{
    public function print($id)
    {
        $transaction = RekeningSaldo::with('rekeningsaldo_items', 'kontak')->findOrFail($id); 
        return view('rekeningsaldo.print', compact('transaction')); 
    }

    public function printVoucher($id)
    {
        $transaction = RekeningSaldo::with('rekeningsaldo_items', 'kontak')->findOrFail($id);
        return view('rekeningsaldo.voucher', compact('transaction'));
    }
    
}
