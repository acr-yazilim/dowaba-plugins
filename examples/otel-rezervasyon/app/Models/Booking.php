<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_id',
        'reservation_code',
        'guest_name',
        'guest_phone',
        'guest_email',
        'check_in',
        'check_out',
        'guests_count',
        'total_amount',
        'status',
        'notes',
        'reminded_at',
        'cancelled_at',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'guests_count' => 'integer',
            'total_amount' => 'decimal:2',
            'reminded_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function nights(): int
    {
        return $this->check_in->diffInDays($this->check_out);
    }

    public static function generateReservationCode(): string
    {
        return 'RZV-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
