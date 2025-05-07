<?php

namespace App\Filament\Resources\RekeningSaldoResource\Widgets;

use Illuminate\Support\Number;
use App\Models\rekeningsaldoitem;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class RekeningSaldoStats extends BaseWidget
{
    protected function getStats(): array
    {        

        $totalIncome = rekeningsaldoitem::query()
        ->join('rekeningsaldos', 'rekeningsaldoitems.rekeningsaldo_id', '=', 'rekeningsaldos.id')  // Melakukan join dengan tabel 'saldos'
        ->where('rekeningsaldos.kas_bank', 'Terima Dana')  // Menambahkan kondisi pada kolom 'kas_bank'
        ->sum('rekeningsaldoitems.biaya');  // Mengambil total 'biaya' dari tabel 'saldoitems'    

        // Total pengeluaran dari semua transaksi yang sudah lunas
        $totalExpense = DB::table('transaction_items')
        ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
        // ->where('transactions.lunas', false)  // Hanya transaksi yang lunas
        ->where('transactions.bayar_dari', '1-00002 - Rekening')  // Hanya transaksi yang dibayar dari "Kas"
        ->where('transactions.status', 'paid')  // Transaksi yang belum lunas
        ->sum('transaction_items.biaya'); 

        // Hitung saldo bersih petty cash
        $netBalance = $totalIncome - $totalExpense;

        return [
            Stat::make('Rekening Masuk', Number::currency($totalIncome, 'IDR'))
            ->description('Total terima dana (voucer)')
            ->icon('heroicon-o-currency-dollar'),

            Stat::make('Transaksi Keluar - Lunas', Number::currency($totalExpense, 'IDR'))
                ->description('Total biaya (invoice) yang telah dibayar')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Saldo Rekening', Number::currency($netBalance, 'IDR'))
            ->description('Total Saldo Sekarang')
            ->color($netBalance >= 0 ? 'success' : 'danger')
            ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
