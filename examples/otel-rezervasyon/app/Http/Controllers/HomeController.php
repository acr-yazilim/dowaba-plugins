<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $today = today();
        $hotel = Hotel::first();

        return view('home', [
            'hotel' => $hotel,
            'totalRooms' => Room::count(),
            'activeBookings' => Booking::whereIn('status', ['confirmed', 'checked_in'])->count(),
            'totalRevenue' => Booking::where('status', '!=', 'cancelled')->sum('total_amount'),
            'todayCheckIns' => Booking::query()
                ->whereDate('check_in', $today)
                ->where('status', 'confirmed')
                ->with(['room', 'hotel'])
                ->orderBy('check_in')
                ->get(),
            'todayCheckOuts' => Booking::query()
                ->whereDate('check_out', $today)
                ->where('status', 'checked_in')
                ->with(['room', 'hotel'])
                ->get(),
            'occupancy' => $this->calculateOccupancy(),
        ]);
    }

    private function calculateOccupancy(): int
    {
        $totalRooms = Room::where('is_active', true)->count();
        if ($totalRooms === 0) return 0;

        $occupied = Booking::query()
            ->whereIn('status', ['checked_in'])
            ->whereDate('check_in', '<=', today())
            ->whereDate('check_out', '>', today())
            ->count();

        return (int) round(($occupied / $totalRooms) * 100);
    }
}
