<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\transaction;
use Illuminate\Support\Number;
use App\Models\transactionitem;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class TransactionStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalBelumBayar = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.lunas', true)  // Transaksi yang belum lunas
            ->where('transactions.status', 'paid')  // Transaksi yang belum lunas
            ->sum('transaction_items.biaya');  // Ambil total biaya

        $totalLunas = DB::table('transaction_items')
        ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
        ->where('transactions.lunas', false) // Hanya transaksi yang lunas
        ->where('transactions.status', 'paid')  // Transaksi yang belum lunas
        ->sum('transaction_items.biaya');

        $totalExpense = TransactionItem::query()
        ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id') // Join dengan tabel transactions
        ->where('transactions.status', 'paid')
        ->sum('transaction_items.biaya');

        // $pendingCount = transaction::query()->where('status', 'pending')->count();
        // $pendingSubtotal = transaction::query()->where('status', 'pending')->sum('subtotal');

        // $paidCount = transaction::query()->where('status', 'paid')->count();
        // $paidSubtotal = transaction::query()->where('status', 'paid')->sum('subtotal');

        // $unpaidCount = transaction::query()->where('status', 'canceled')->count();
        // $unpaidSubtotal = transaction::query()->where('status', 'canceled')->sum('subtotal');

        return [
            
            Stat::make('Tagihan - Belum Bayar', Number::currency($totalBelumBayar, 'IDR'))
            ->description('Total tagihan belum dibayar')
            ->icon('heroicon-o-exclamation-circle'), // Ikon untuk status "Belum Bayar"

            Stat::make('Transaksi Keluar - Lunas', Number::currency($totalLunas, 'IDR'))
            ->description('Total biaya (invoice) yang telah dibayar')
            ->icon('heroicon-o-currency-dollar'),
            
            Stat::make('Total Transaksi Keluar', Number::currency($totalExpense, 'IDR'))
            ->description('Total pengeluaran bank (voucher)')
            ->icon('heroicon-o-currency-dollar'),
            
            // Stat::make('Pending Transactions', Number::currency($pendingSubtotal, 'IDR'))
            //     ->description("Total Subtotal ($pendingCount transaksi) yang statusnya pending"),
            //     // ->icon('heroicon-o-pending'),
    
            // // Statistik untuk transaksi Paid
            // Stat::make('Paid Transactions', Number::currency($paidSubtotal, 'IDR'))
            //     ->description("Total Subtotal ($paidCount transaksi) yang telah dibayar"),
            //     // ->icon('heroicon-o-check-circle'),
    
            // // Statistik untuk transaksi Canceled
            // Stat::make('Canceled Transactions', Number::currency($unpaidSubtotal, 'IDR'))
            //     ->description("Total Subtotal ($unpaidCount transaksi) yang dibatalkan"),
            //     // ->icon('heroicon-o-x-circle'),
        ];
    }
}

        // $pendingCount = transaction::query()->where('status', 'pending')->count();
        // $paidCount = transaction::query()->where('status', 'paid')->count();
        // $canceledCount = transaction::query()->where('status', 'canceled')->count();

                // $totalJatuhTempo = DB::table('transactionitems')
        //     ->join('transactions', 'transactionitems.transaction_id', '=', 'transactions.id')
        //     ->where('transactions.lunas', true)  // Transaksi yang belum lunas
        //     ->where('transactions.jatuh_tempo', '<', now())  // Jatuh tempo sudah lewat
        //     ->sum('transactionitems.biaya');  // Ambil total biaya jatuh tempo

        // return [
            // Stat::make('Proses', function () use ($pendingCount, $approvedCount, $canceledCount) {
            //     // Tampilkan jumlah dan ikon Heroicons
            //     return;
            // })
            //     ->icon('heroicon-s-clock') // Ikon untuk status "pending"
            //     ->description("{$pendingCount} Pending | {$approvedCount} Approved | {$canceledCount} Cancelled")
            //     ->color('primary'),

            // Stat::make('Tagihan - Jatuh Tempo', Number::currency($totalJatuhTempo, 'IDR'))
            // ->description('Total tagihan jatuh tempo')
            // ->icon('heroicon-o-clock'),

// Stat::make('Total Pengeluaran', Number::currency(transaction::query()->sum('subtotal'), 'IDR')),
// Stat::make('Pengeluaran Rata-Rata', Number::currency(transaction::query()->avg('subtotal'), 'IDR')),
// Stat::make('Saldo Akhir', Number::currency(transaction::query()->avg('subtotal'), 'IDR')),