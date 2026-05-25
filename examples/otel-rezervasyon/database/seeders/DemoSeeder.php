<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::firstOrCreate(
            ['name' => 'Demo Otel İstanbul'],
            [
                'city' => 'İstanbul',
                'address' => 'Beyoğlu, Galip Dede Cd. No:12',
                'phone' => '+902120000099',
                'star_rating' => 4,
                'check_in_time' => '14:00',
                'check_out_time' => '11:00',
            ]
        );

        $roomDefs = [
            ['number' => '101', 'type' => 'single', 'capacity' => 1, 'price' => 1200],
            ['number' => '102', 'type' => 'single', 'capacity' => 1, 'price' => 1200],
            ['number' => '201', 'type' => 'double', 'capacity' => 2, 'price' => 1800],
            ['number' => '202', 'type' => 'double', 'capacity' => 2, 'price' => 1800],
            ['number' => '203', 'type' => 'double', 'capacity' => 2, 'price' => 1900],
            ['number' => '301', 'type' => 'suite', 'capacity' => 3, 'price' => 3500],
            ['number' => '401', 'type' => 'family', 'capacity' => 4, 'price' => 2800],
            ['number' => '402', 'type' => 'family', 'capacity' => 4, 'price' => 2800],
        ];

        $rooms = collect($roomDefs)->map(fn ($r) => Room::firstOrCreate(
            ['hotel_id' => $hotel->id, 'room_number' => $r['number']],
            [
                'type' => $r['type'],
                'capacity' => $r['capacity'],
                'price_per_night' => $r['price'],
                'is_active' => true,
            ]
        ));

        $guests = [
            ['name' => 'Ahmet Yılmaz', 'phone' => '+905551110201', 'email' => 'ahmet@example.com'],
            ['name' => 'Selin Kara', 'phone' => '+905551110202', 'email' => 'selin@example.com'],
            ['name' => 'Mehmet Aksoy', 'phone' => '+905551110203', 'email' => 'mehmet@example.com'],
            ['name' => 'Ayşe Demir', 'phone' => '+905551110204', 'email' => 'ayse@example.com'],
            ['name' => 'Burak Şahin', 'phone' => '+905551110205', 'email' => 'burak@example.com'],
            ['name' => 'Cansu Polat', 'phone' => '+905551110206', 'email' => 'cansu@example.com'],
            ['name' => 'Deniz Aydın', 'phone' => '+905551110207', 'email' => 'deniz@example.com'],
            ['name' => 'Elif Yıldız', 'phone' => '+905551110208', 'email' => 'elif@example.com'],
        ];

        // Geçmiş, bugün, yarın, sonraki günler için 12 rezervasyon
        $offsets = [-7, -3, -1, 0, 0, 1, 1, 2, 4, 7, 14, 30];

        foreach ($offsets as $i => $offset) {
            $checkIn = Carbon::today()->addDays($offset);
            $nights = rand(1, 3);
            $checkOut = $checkIn->copy()->addDays($nights);
            $room = $rooms->random();
            $guest = $guests[$i % count($guests)];

            $status = match (true) {
                $offset < -1 => 'checked_out',
                $offset === -1 || $offset === 0 => ($i % 2 === 0 ? 'checked_in' : 'checked_out'),
                default => 'confirmed',
            };

            Booking::firstOrCreate(
                ['reservation_code' => sprintf('RZV-DEMO%03d', $i + 1)],
                [
                    'hotel_id' => $hotel->id,
                    'room_id' => $room->id,
                    'guest_name' => $guest['name'],
                    'guest_phone' => $guest['phone'],
                    'guest_email' => $guest['email'],
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'guests_count' => min($room->capacity, rand(1, 3)),
                    'total_amount' => $nights * (float) $room->price_per_night,
                    'status' => $status,
                    'checked_in_at' => in_array($status, ['checked_in', 'checked_out']) ? $checkIn->copy()->setTime(14, 30) : null,
                    'notes' => $i % 4 === 0 ? 'Erken check-in talebi var.' : null,
                ]
            );
        }

        $this->command->info('Demo seed: 1 otel + '.$rooms->count().' oda + '.Booking::count().' rezervasyon.');
    }
}
