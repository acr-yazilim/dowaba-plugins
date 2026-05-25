<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = collect([
            ['name' => 'Dr. Mehmet Yılmaz', 'specialty' => 'Dahiliye', 'phone' => '+905320000001', 'email' => 'm.yilmaz@demohastane.com'],
            ['name' => 'Dr. Ayşe Demir', 'specialty' => 'Kardiyoloji', 'phone' => '+905320000002', 'email' => 'a.demir@demohastane.com'],
            ['name' => 'Dr. Can Çelik', 'specialty' => 'Ortopedi', 'phone' => '+905320000003', 'email' => 'c.celik@demohastane.com'],
        ])->map(fn ($d) => Doctor::firstOrCreate(['email' => $d['email']], $d));

        $patients = collect([
            ['name' => 'Ali Kaya', 'phone' => '+905551110001', 'email' => 'ali@example.com'],
            ['name' => 'Zeynep Arslan', 'phone' => '+905551110002', 'email' => 'zeynep@example.com'],
            ['name' => 'Mustafa Şahin', 'phone' => '+905551110003', 'email' => 'mustafa@example.com'],
            ['name' => 'Elif Yıldız', 'phone' => '+905551110004', 'email' => 'elif@example.com'],
            ['name' => 'Burak Polat', 'phone' => '+905551110005', 'email' => 'burak@example.com'],
        ])->map(fn ($p) => Patient::firstOrCreate(['phone' => $p['phone']], $p));

        $offsets = [-2, -1, 0, 1, 1, 2, 3, 7, 14, 30]; // gün cinsinden bugünden uzaklık

        foreach ($offsets as $i => $offset) {
            $scheduledAt = Carbon::now()->addDays($offset)->setTime(rand(9, 16), [0, 15, 30, 45][rand(0, 3)]);

            Appointment::firstOrCreate(
                [
                    'doctor_id' => $doctors->random()->id,
                    'patient_id' => $patients->random()->id,
                    'scheduled_at' => $scheduledAt,
                ],
                [
                    'status' => $offset < 0 ? 'completed' : 'confirmed',
                    'confirmed_at' => $offset < 0 ? Carbon::now()->subDays(abs($offset) + 1) : Carbon::now()->subDay(),
                    'notes' => $i % 3 === 0 ? 'Rutin kontrol' : null,
                ]
            );
        }

        $this->command->info("Demo seed: {$doctors->count()} doktor, {$patients->count()} hasta, ".Appointment::count().' randevu.');
    }
}
