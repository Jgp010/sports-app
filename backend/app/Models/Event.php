<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sport_id', 'title', 'slug', 'description', 'venue', 'event_start_at', 'event_end_at',
        'registration_open_at', 'registration_close_at', 'capacity', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'event_start_at' => 'datetime',
            'event_end_at' => 'datetime',
            'registration_open_at' => 'datetime',
            'registration_close_at' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EventItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isRegistrationOpen(): bool
    {
        $now = now();

        return $this->status === 'published'
            && $now->betweenIncluded($this->registration_open_at, $this->registration_close_at)
            && ($this->capacity === null || $this->activeRegistrationsCount() < $this->capacity);
    }

    public function activeRegistrationsCount(): int
    {
        if (array_key_exists('active_registrations_count', $this->attributes)) {
            return (int) $this->attributes['active_registrations_count'];
        }

        return $this->registrations()->where('status', 'registered')->count();
    }
}
