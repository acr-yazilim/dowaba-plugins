<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use Dowaba\LaravelBridge\Facades\Dowaba;
use Dowaba\LaravelBridge\Support\DowabaException;
use Illuminate\Console\Command;
use Throwable;

class SendCheckInReminders extends Command
{
    protected $signature = 'bookings:send-checkin-reminders {--days=1 : Kaç gün öncesi hatırlatma}';

    protected $description = 'Yarın check-in yapacak misafirlere WhatsApp template ile hatırlatma gönder';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $targetDate = now()->addDays($days)->toDateString();

        $bookings = Booking::query()
            ->with(['hotel', 'room'])
            ->whereDate('check_in', $targetDate)
            ->where('status', 'confirmed')
            ->whereNull('reminded_at')
            ->whereNull('cancelled_at')
            ->get();

        $this->info("Hatırlatma gönderilecek rezervasyon sayısı: {$bookings->count()} (tarih: {$targetDate})");

        $sent = 0;
        $failed = 0;

        foreach ($bookings as $booking) {
            try {
                Dowaba::whatsapp()->template(
                    phone: $booking->guest_phone,
                    template: 'hotel_checkin_reminder',
                    params: [
                        'name' => $booking->guest_name,
                        'hotel' => $booking->hotel->name,
                        'reservation_code' => $booking->reservation_code,
                        'check_in' => $booking->check_in->format('d M Y'),
                        'check_in_time' => $booking->hotel->check_in_time,
                        'address' => $booking->hotel->address ?? '',
                        'phone' => $booking->hotel->phone ?? '',
                    ],
                    siteId: (int) config('dowaba.widget.site_id'),
                );

                $booking->update(['reminded_at' => now()]);
                $sent++;

                $this->line("  ✓ {$booking->guest_name} ({$booking->guest_phone}) — {$booking->reservation_code}");
            } catch (DowabaException $e) {
                $failed++;
                $this->error("  ✗ {$booking->guest_name}: {$e->getMessage()}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("  ✗ {$booking->guest_name}: ".$e::class.' - '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Gönderilen: {$sent} / Başarısız: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
