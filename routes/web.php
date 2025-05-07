<?php

use App\Livewire\HomePage;
use App\Exports\TemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaldoController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\RekeningSaldoController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', HomePage::class);

Route::get('transaction/print/{id}', [TransactionController::class, 'print'])->name('transaction.print');

Route::get('saldo/print/{id}', [SaldoController::class, 'print'])->name('saldo.print');
Route::get('saldo/voucher/{id}', [SaldoController::class, 'printVoucher'])->name('saldo.voucher');
Route::get('rekeningsaldo/print/{id}', [RekeningSaldoController::class, 'print'])->name('rekeningsaldo.print');
Route::get('rekeningsaldo/voucher/{id}', [RekeningSaldoController::class, 'printVoucher'])->name('rekeningsaldo.voucher');

Route::get('/download-template', function(){
    return Excel::download(new TemplateExport, 'template.xlsx');
})->name('download-template');