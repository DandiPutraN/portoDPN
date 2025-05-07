<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Expense;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $product_total = Product::count();
        $order_total = Order::count();
        $revenue = Order::sum('total_price');
        $expenses = Expense::sum('amount');
        
        return [
            Stat::make('Total Product', $product_total),
            Stat::make('Total Order', $order_total),
            Stat::make('Total Revenue', 'Rp. ' . number_format($revenue, 0, ',', '.')),
            Stat::make('Total Expenses', 'Rp. ' . number_format($expenses, 0, ',', '.')),
        ];
    }
}
