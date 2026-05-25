<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Dowaba\LaravelBridge\Facades\Dowaba;
use Dowaba\LaravelBridge\Support\DowabaException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    public function index(): View
    {
        $appointments = Appointment::query()
            ->with(['doctor', 'patient'])
            ->orderBy('scheduled_at')
            ->paginate(20);

        return view('appointments.index', compact('appointments'));
    }

    public function create(): View
    {
        return view('appointments.create', [
            'doctors' => Doctor::orderBy('name')->get(),
            'patients' => Patient::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data['status'] = 'confirmed';
        $data['confirmed_at'] = now();

        $appointment = Appointment::create($data);
        $appointment->load(['doctor', 'patient']);

        // Randevu onay mesajı — anında WhatsApp template
        try {
            Dowaba::whatsapp()->template(
                phone: $appointment->patient->phone,
                template: 'appointment_confirmation',
                params: [
                    'name' => $appointment->patient->name,
                    'doctor' => $appointment->doctor->name,
                    'date' => $appointment->scheduled_at->format('d M Y H:i'),
                ],
                siteId: (int) config('dowaba.widget.site_id'),
            );
        } catch (DowabaException $e) {
            Log::warning('Dowaba randevu onay mesajı gönderilemedi', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'error_code' => $e->errorCode(),
            ]);
        }

        return redirect()->route('appointments.index')
            ->with('status', "Randevu oluşturuldu: {$appointment->patient->name} — {$appointment->scheduled_at->format('d M Y H:i')}");
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return redirect()->route('appointments.index')
            ->with('status', 'Randevu iptal edildi.');
    }
}
