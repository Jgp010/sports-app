<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EventItem extends Model
{
    protected $fillable = [
        'event_id', 'name', 'description', 'registration_fee', 'early_bird_fee',
        'early_bird_ends_at', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'registration_fee' => 'integer',
            'early_bird_fee' => 'integer',
            'early_bird_ends_at' => 'datetime',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(EventRegistration::class, 'event_registration_items')
            ->withPivot('unit_price', 'created_at');
    }

    public function currentPrice(): int
    {
        if ($this->early_bird_fee !== null
            && $this->early_bird_ends_at !== null
            && now()->lessThanOrEqualTo($this->early_bird_ends_at)) {
            return (int) $this->early_bird_fee;
        }

        return (int) $this->registration_fee;
    }
}
