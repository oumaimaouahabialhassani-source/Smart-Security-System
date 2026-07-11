<?php

namespace App\Models;

use App\Enums\AccessResult;
use App\Enums\EventSeverity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessEvent extends Model
{
    /** @use HasFactory<\Database\Factories\AccessEventFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'kind',
        'user_id',
        'visit_id',
        'person_name',
        'badge_id',
        'door_id',
        'direction',
        'result',
        'severity',
        'method',
        'device_id',
        'camera_id',
        'face_confidence',
        'ip_address',
        'detail',
        'happened_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result' => AccessResult::class,
            'severity' => EventSeverity::class,
            'face_confidence' => 'integer',
            'happened_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function door(): BelongsTo
    {
        return $this->belongsTo(Door::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    /**
     * Scope: badge/biometric access attempts (the logs table).
     */
    public function scopeAccess(Builder $query): Builder
    {
        return $query->where('kind', 'access');
    }

    /**
     * Scope: security incidents (the timeline).
     */
    public function scopeSecurity(Builder $query): Builder
    {
        return $query->where('kind', 'security');
    }

    /**
     * Scope: events recorded today.
     */
    public function scopeToday(Builder $query): Builder
    {
        // Range instead of whereDate() so the happened_at index is used.
        return $query->whereBetween('happened_at', [today(), today()->endOfDay()]);
    }

    /**
     * Scope: search by person name, badge ID or detail.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('person_name', 'like', "%{$term}%")
                    ->orWhere('badge_id', 'like', "%{$term}%")
                    ->orWhere('detail', 'like', "%{$term}%");
            });
        });
    }
}
