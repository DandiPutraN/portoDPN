<?php

namespace App\Filament\Widgets;

use App\Models\Saldo;
use App\Models\SaldoItem;
use App\Models\transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use App\Models\TransactionItem;
use App\Models\Rekeningsaldoitem;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsWidget extends BaseWidget
{
    // use InteractsWithPageFilters;

    protected function getStats(): array
    {
        // Validasi filter tanggal
        $start = isset($this->filters['startDate']) ? Carbon::parse($this->filters['startDate']) : now()->startOfYear();
        $end = isset($this->filters['endDate']) ? Carbon::parse($this->filters['endDate']) : now();

        // Penghitungan Kas
        $kasData = $this->calculateBalanceData('1-00001 - Kas', 'Terima Dana', $start, $end);

        // Penghitungan Rekening
        $rekeningData = $this->calculateBalanceData('1-00002 - Rekening', 'Terima Dana', $start, $end);

        $totalIncome = Rekeningsaldoitem::query()
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

        // Grafik Saldo Kas
        $kasChartData = $this->generateChartData('1-00001 - Kas', $start, $end);

        // Grafik Saldo Rekening
        $rekeningChartData = $this->generateChartData('1-00002 - Rekening', $start, $end);

        return [
            // Stats Saldo Kas
            Stat::make('Kas Masuk', Number::currency($kasData['income'], 'IDR'))
                ->description('Total terima dana (voucher)')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Kas Keluar', Number::currency($kasData['expense'], 'IDR'))
                ->description('Total pengeluaran (voucher)')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Petty Cash', Number::currency($kasData['net'], 'IDR'))
                ->description('Total saldo sekarang')
                ->color($kasData['net'] >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-currency-dollar')
                ->chart($kasChartData), 

            // Stats Saldo Rekening
            Stat::make('Rekening Masuk', Number::currency($totalIncome, 'IDR'))
            ->description('Total terima dana (voucer)')
            ->icon('heroicon-o-currency-dollar'),

            Stat::make('Transaksi Keluar - Lunas', Number::currency($totalExpense, 'IDR'))
                ->description('Total biaya (invoice) yang telah dibayar')
                ->icon('heroicon-o-currency-dollar'),

            // Stat::make('Saldo Rekening', Number::currency($rekeningData['net'], 'IDR'))
            // ->description('Total saldo sekarang')
            // ->color($rekeningData['net'] >= 0 ? 'success' : 'danger')
            // ->icon('heroicon-o-currency-dollar'),

            Stat::make('Saldo Rekening', Number::currency($netBalance, 'IDR'))
            ->description('Total Saldo Sekarang')
            ->color($netBalance >= 0 ? 'success' : 'danger')
            ->icon('heroicon-o-currency-dollar')
            ->chart($rekeningChartData),
        ];
    }

    private function calculateBalanceData($source, $kasBankCondition, $start, $end): array
    {
        // Total pemasukan
        $income = DB::table('saldo_items')
            ->join('saldos', 'saldo_items.saldo_id', '=', 'saldos.id')
            ->where('saldos.kas_bank', $kasBankCondition)
            ->whereBetween('saldos.created_at', [$start, $end])
            ->sum('saldo_items.biaya');

        // Total pengeluaran
        $expense = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.bayar_dari', $source)
            ->where('transactions.status', 'paid')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->sum('transaction_items.biaya');

        // Hitung saldo bersih
        $net = $income - $expense;

        return compact('income', 'expense', 'net');
    }

    private function generateChartData($source, $start, $end)
    {
        return DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.bayar_dari', $source)
            ->whereBetween('transactions.created_at', [$start, $end])
            ->selectRaw('MONTH(transactions.created_at) as month, SUM(transaction_items.biaya) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->values()
            ->toArray();
    }
}


// Tagihan Belum Bayar per bulan
    // $belumBayarChartData = DB::table('transactionitems')
    // ->join('transactions', 'transactionitems.transaction_id', '=', 'transactions.id')
    // ->where('transactions.lunas', true)
    // ->where('transactions.status', 'paid')
    // ->select(DB::raw('MONTH(transactions.created_at) as month'), DB::raw('SUM(transactionitems.biaya) as total'))
    // ->groupBy(DB::raw('MONTH(transactions.created_at)'))
    // ->orderBy(DB::raw('MONTH(transactions.created_at)'))
    // ->take(7)  // Ambil data 7 bulan terakhir
    // ->pluck('total', 'month');  // Ambil total biaya per bulan

    // return:
    
    // Stat::make('Transaksi Keluar - Lunas', Number::currency($totalLunas, 'IDR'))
    //     ->description('Total biaya (invoice) yang telah dibayar')
    //     ->icon('heroicon-o-currency-dollar')
    //     ->color('danger')  // Warna merah untuk transaksi lunas
    //     ->chart($lunasChartData->values()->toArray()),  // Mengonversi Collection ke array

    // Stat::make('Tagihan - Belum Bayar', Number::currency($totalBelumBayar, 'IDR'))
    // ->description('Total tagihan yang belum dibayar')
    // ->icon('heroicon-o-clock')  // Ikon untuk status "Belum Bayar"
    // ->color('warning')  // Warna oranye untuk tagihan yang belum dibayar
    // ->chart($belumBayarChartData->values()->toArray()), // Mengonversi Collection ke array

// class StatsWidget extends BaseWidget
// {
//     protected function getStats(): array
//     {
        
//         $totalBelumBayar = DB::table('transactionitems')
//         ->join('transactions', 'transactionitems.transaction_id', '=', 'transactions.id')
//         ->where('transactions.lunas', true)  // Transaksi yang belum lunas
//         ->where('transactions.status', 'paid')  // Transaksi yang belum lunas
//         ->sum('transactionitems.biaya');  // Ambil total biaya
        
//         $totalLunas = DB::table('transactionitems')
//         ->join('transactions', 'transactionitems.transaction_id', '=', 'transactions.id')
//         ->where('transactions.lunas', false) // Hanya transaksi yang lunas
//         ->where('transactions.status', 'paid')  // Transaksi yang belum lunas
//         ->sum('transactionitems.biaya');

//         $totalExpense = transactionitem::query()
//         ->join('transactions', 'transactionitems.transaction_id', '=', 'transactions.id') // Join dengan tabel transactions
//         ->where('transactions.status', 'paid')
//         ->sum('transactionitems.biaya');

//         $totalIncome = saldoitem::query()
//         ->join('saldos', 'saldoitems.saldo_id', '=', 'saldos.id')  // Melakukan join dengan tabel 'saldos'
//         ->where('saldos.kas_bank', 'Terima Dana')  // Menambahkan kondisi pada kolom 'kas_bank'
//         ->sum('saldoitems.biaya');  // Mengambil total 'biaya' dari tabel 'saldoitems'  
        
//         $netBalance = $totalIncome - $totalExpense;

//         return [
//             Stat::make('Tagihan - Belum Bayar', Number::currency($totalBelumBayar, 'IDR'))
//             ->description('Total tagihan yang belum dibayar')
//             ->icon('heroicon-o-clock')  // Ikon untuk status "Belum Bayar"
//             ->color('warning')  // Warna oranye untuk tagihan yang belum dibayar
//             ->chart([30, 100, 150, 120, 170, 200, 250]), // Data dummy untuk tagihan belum dibayar
        
//         Stat::make('Transaksi Keluar - Lunas', Number::currency($totalLunas, 'IDR'))
//             ->description('Total biaya (invoice) yang telah dibayar')
//             ->icon('heroicon-o-currency-dollar')
//             ->color('danger')  // Warna merah untuk transaksi lunas
//             ->chart([50, 15, 200, 350, 500, 75, 100]),  // Data chart menunjukkan fluktuasi transaksi lunas
        
//         Stat::make('Petty Cash', Number::currency($netBalance, 'IDR'))
//             ->description('Total Saldo Sekarang')
//             ->color($netBalance >= 0 ? 'success' : 'danger')  // Warna hijau jika saldo positif, merah jika negatif
//             ->icon('heroicon-o-currency-dollar')
//             ->chart([5, 15, 20, 35, 50, 75, 100])  // Data chart menunjukkan perubahan saldo kas (petty cash)
        
            
//             // Stat::make('New Transaction', transaction::count()),
//             // Stat::make('New Saldo', saldo::count())
//         ];
//     }
// }
