<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'user_id', 'registration_no', 'status', 'contact_phone', 'organization', 'total_amount',
        'emergency_contact_name', 'emergency_contact_phone', 'notes', 'admin_notes',
        'registered_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return ['registered_at' => 'datetime', 'cancelled_at' => 'datetime', 'total_amount' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(EventItem::class, 'event_registration_items')
            ->withPivot('unit_price', 'created_at');
    }
}
