<?php

namespace App\Filament\Resources\SaldoResource\Widgets;

use App\Models\saldoItem;
use App\Models\transactionitem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SaldoStats extends BaseWidget
{
    protected function getStats(): array
    {        

        $totalIncome = SaldoItem::query()
        ->join('saldos', 'saldo_items.saldo_id', '=', 'saldos.id')  // Melakukan join dengan tabel 'saldos'
        ->where('saldos.kas_bank', 'Terima Dana')  // Menambahkan kondisi pada kolom 'kas_bank'
        ->sum('saldo_items.biaya');  // Mengambil total 'biaya' dari tabel 'saldoitems'    

        // Total pengeluaran dari semua transaksi yang sudah lunas
        $totalExpense = DB::table('transaction_items')
        ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
        // ->where('transactions.lunas', false)  // Hanya transaksi yang lunas
        ->where('transactions.bayar_dari', '1-00001 - Kas')  // Hanya transaksi yang dibayar dari "Kas"
        ->where('transactions.status', 'paid')  // Transaksi yang belum lunas
        ->sum('transaction_items.biaya'); 

        // Hitung saldo bersih petty cash
        $netBalance = $totalIncome - $totalExpense;

        return [
            Stat::make('Kas Masuk', Number::currency($totalIncome, 'IDR'))
            ->description('Total terima dana (voucer)')
            ->icon('heroicon-o-currency-dollar'),

            Stat::make('Transaksi Keluar - Lunas', Number::currency($totalExpense, 'IDR'))
                ->description('Total biaya (invoice) yang telah dibayar')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Petty Cash', Number::currency($netBalance, 'IDR'))
            ->description('Total Saldo Sekarang')
            ->color($netBalance >= 0 ? 'success' : 'danger')
            ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
