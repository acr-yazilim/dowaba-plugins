<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'totalAppointments' => Appointment::count(),
            'upcomingAppointments' => Appointment::query()
                ->where('scheduled_at', '>=', now())
                ->where('status', 'confirmed')
                ->count(),
            'totalPatients' => Patient::count(),
            'totalDoctors' => Doctor::count(),
            'todayAppointments' => Appointment::query()
                ->whereDate('scheduled_at', today())
                ->with(['doctor', 'patient'])
                ->orderBy('scheduled_at')
                ->get(),
        ]);
    }
}
