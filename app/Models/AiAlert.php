<?php

namespace App\Models;

use App\Enums\AiAlertStatus;
use App\Enums\AiRecommendation;
use App\Enums\AiRiskLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAlert extends Model
{
    /** @use HasFactory<\Database\Factories\AiAlertFactory> */
    use HasFactory;

    /**
     * Every event category the AI Security Bot monitors.
     *
     * @var list<string>
     */
    public const EVENT_TYPES = [
        'Employee Check In', 'Employee Check Out',
        'Visitor Registration', 'Visitor Exit',
        'Door Open', 'Door Closed', 'Unauthorized Door Access',
        'Camera Offline', 'IoT Device Offline',
        'Multiple Failed Login Attempts',
        'Face Recognition Success', 'Face Recognition Failed',
        'Unknown Face Detection', 'Motion Detection',
        'Blacklisted Visitor', 'After-Hours Activity',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ai_code',
        'event_type',
        'description',
        'risk_level',
        'risk_score',
        'analysis',
        'recommendation',
        'camera_id',
        'device_id',
        'door_id',
        'user_id',
        'visit_id',
        'location',
        'building',
        'floor',
        'status',
        'reviewed_by',
        'notes',
        'notified_channels',
        'source_type',
        'source_id',
        'resolved_at',
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
            'risk_level' => AiRiskLevel::class,
            'risk_score' => 'integer',
            'recommendation' => AiRecommendation::class,
            'status' => AiAlertStatus::class,
            'notified_channels' => 'array',
            'resolved_at' => 'datetime',
            'happened_at' => 'datetime',
        ];
    }

    /**
     * Assign a sequential AI alert code once the row exists.
     */
    protected static function booted(): void
    {
        static::created(function (AiAlert $alert) {
            if (! $alert->ai_code) {
                $alert->ai_code = 'AIB-'.str_pad((string) $alert->id, 5, '0', STR_PAD_LEFT);
                $alert->saveQuietly();
            }
        });
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function door(): BelongsTo
    {
        return $this->belongsTo(Door::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope: alerts still needing attention.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [AiAlertStatus::New, AiAlertStatus::Reviewing]);
    }

    /**
     * Scope: detected today.
     */
    public function scopeToday(Builder $query): Builder
    {
        // Range instead of whereDate() so the happened_at index is used.
        return $query->whereBetween('happened_at', [today(), today()->endOfDay()]);
    }

    /**
     * Scope: search by code, event type, description or analysis.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('ai_code', 'like', "%{$term}%")
                    ->orWhere('event_type', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('analysis', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Where the event happened, for the table's Location column.
     */
    public function locationLabel(): string
    {
        return $this->location
            ?? $this->door?->name
            ?? $this->camera?->location
            ?? $this->device?->placement()
            ?? (trim(($this->building ?? '').' '.($this->floor ?? '')) ?: 'Facility');
    }

    /**
     * The person the alert refers to (employee or visitor).
     */
    public function personLabel(): ?string
    {
        return $this->user?->name ?? $this->visit?->full_name;
    }
}
