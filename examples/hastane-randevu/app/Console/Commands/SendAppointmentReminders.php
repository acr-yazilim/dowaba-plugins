<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Appointment;
use Dowaba\LaravelBridge\Facades\Dowaba;
use Dowaba\LaravelBridge\Support\DowabaException;
use Illuminate\Console\Command;
use Throwable;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders {--hours=24 : Kaç saat öncesi hatırlatma}';

    protected $description = '24 saat (default) önündeki randevulara WhatsApp template ile hatırlatma gönder';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $windowStart = now()->addHours($hours - 1);
        $windowEnd = now()->addHours($hours + 1);

        $appointments = Appointment::query()
            ->with(['patient', 'doctor'])
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->where('status', 'confirmed')
            ->whereNull('reminded_at')
            ->whereNull('cancelled_at')
            ->get();

        $this->info("Hatırlatma gönderilecek randevu sayısı: {$appointments->count()}");

        $sent = 0;
        $failed = 0;

        foreach ($appointments as $appt) {
            try {
                Dowaba::whatsapp()->template(
                    phone: $appt->patient->phone,
                    template: 'appointment_reminder',
                    params: [
                        'name' => $appt->patient->name,
                        'doctor' => $appt->doctor->name,
                        'date' => $appt->scheduled_at->format('d M Y H:i'),
                        'specialty' => $appt->doctor->specialty ?? '',
                    ],
                    siteId: (int) config('dowaba.widget.site_id'),
                );

                $appt->update(['reminded_at' => now()]);
                $sent++;

                $this->line("  ✓ {$appt->patient->name} ({$appt->patient->phone})");
            } catch (DowabaException $e) {
                $failed++;
                $this->error("  ✗ {$appt->patient->name}: {$e->getMessage()}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("  ✗ {$appt->patient->name}: ".$e::class.' - '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Gönderilen: {$sent} / Başarısız: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
