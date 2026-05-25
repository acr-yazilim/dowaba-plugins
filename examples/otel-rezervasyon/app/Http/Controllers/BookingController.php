<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Dowaba\LaravelBridge\Facades\Dowaba;
use Dowaba\LaravelBridge\Support\DowabaException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function index(): View
    {
        $bookings = Booking::query()
            ->with(['hotel', 'room'])
            ->orderByDesc('check_in')
            ->paginate(20);

        return view('bookings.index', compact('bookings'));
    }

    public function create(): View
    {
        return view('bookings.create', [
            'hotels' => Hotel::with('rooms')->get(),
            'rooms' => Room::where('is_active', true)->orderBy('room_number')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'room_id' => 'required|exists:rooms,id',
            'guest_name' => 'required|string|max:120',
            'guest_phone' => 'required|string|max:32',
            'guest_email' => 'nullable|email|max:120',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests_count' => 'required|integer|min:1|max:8',
            'notes' => 'nullable|string|max:1000',
        ]);

        $room = Room::findOrFail($data['room_id']);
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        if (! $room->isAvailable($checkIn, $checkOut)) {
            return back()->withInput()->withErrors([
                'room_id' => "Oda {$room->room_number} seçilen tarihlerde dolu.",
            ]);
        }

        $nights = $checkIn->diffInDays($checkOut);
        $data['total_amount'] = $nights * (float) $room->price_per_night;
        $data['reservation_code'] = Booking::generateReservationCode();
        $data['status'] = 'confirmed';

        $booking = Booking::create($data);
        $booking->load(['hotel', 'room']);

        // Rezervasyon onay → WhatsApp template
        try {
            Dowaba::whatsapp()->template(
                phone: $booking->guest_phone,
                template: 'hotel_booking_confirmation',
                params: [
                    'name' => $booking->guest_name,
                    'hotel' => $booking->hotel->name,
                    'reservation_code' => $booking->reservation_code,
                    'check_in' => $booking->check_in->format('d M Y'),
                    'check_out' => $booking->check_out->format('d M Y'),
                    'room_type' => $booking->room->type,
                    'total' => number_format((float) $booking->total_amount, 2).' TL',
                ],
                siteId: (int) config('dowaba.widget.site_id'),
            );
        } catch (DowabaException $e) {
            Log::warning('Dowaba hotel booking confirmation gönderilemedi', [
                'booking_id' => $booking->id,
                'reservation_code' => $booking->reservation_code,
                'error' => $e->getMessage(),
                'error_code' => $e->errorCode(),
            ]);
        }

        return redirect()->route('bookings.index')
            ->with('status', "Rezervasyon oluşturuldu: {$booking->reservation_code} — {$booking->guest_name}");
    }

    public function checkin(Booking $booking): RedirectResponse
    {
        $booking->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        return redirect()->route('bookings.index')
            ->with('status', "Check-in tamamlandı: {$booking->reservation_code}");
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return redirect()->route('bookings.index')
            ->with('status', "Rezervasyon iptal edildi: {$booking->reservation_code}");
    }
}
