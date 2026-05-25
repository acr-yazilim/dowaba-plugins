<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'totalOrders' => Order::count(),
            'pendingOrders' => Order::where('status', 'pending')->count(),
            'shippedOrders' => Order::where('status', 'shipped')->count(),
            'totalRevenue' => Order::where('status', '!=', 'cancelled')->sum('total_amount'),
            'totalCustomers' => Customer::count(),
            'totalProducts' => Product::where('is_active', true)->count(),
            'lowStock' => Product::where('is_active', true)->where('stock', '<', 5)->get(),
            'recentOrders' => Order::query()
                ->with(['customer', 'items'])
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
