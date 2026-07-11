<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Notifications\SecurityAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;

class Alert extends Model
{
    /** @use HasFactory<\Database\Factories\AlertFactory> */
    use HasFactory;

    /**
     * Every alert category the system can raise.
     *
     * @var list<string>
     */
    public const TYPES = [
        'Unauthorized Access', 'Door Forced Open', 'Door Left Open',
        'Camera Offline', 'Camera Tampering', 'Motion Detected',
        'Unknown Face Detected', 'Multiple Failed Login Attempts',
        'Expired Badge Used', 'Fingerprint Verification Failed',
        'RFID Authentication Failed', 'Intrusion Detected',
        'Fire Alarm', 'Smoke Detection', 'Glass Break Detection',
        'Emergency Exit Opened', 'Network Failure', 'Server Offline',
        'Power Failure', 'Low Battery Device', 'IoT Device Offline',
        'AI Suspicious Activity', 'System Error',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'alert_code',
        'type',
        'severity',
        'status',
        'description',
        'device_id',
        'camera_id',
        'door_id',
        'user_id',
        'visit_id',
        'building',
        'floor',
        'ai_confidence',
        'assigned_to',
        'notes',
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
            'severity' => AlertSeverity::class,
            'status' => AlertStatus::class,
            'ai_confidence' => 'integer',
            'resolved_at' => 'datetime',
            'happened_at' => 'datetime',
        ];
    }

    /**
     * Assign a sequential alert code once the row exists.
     */
    protected static function booted(): void
    {
        static::created(function (Alert $alert) {
            if (! $alert->alert_code) {
                $alert->alert_code = 'ALT-'.str_pad((string) $alert->id, 5, '0', STR_PAD_LEFT);
                $alert->saveQuietly();
            }
        });
    }

    /**
     * Raise a new alert from anywhere in the system.
     *
     * @param array<string, mixed> $attributes
     */
    public static function raise(string $type, AlertSeverity $severity, string $description, array $attributes = []): self
    {
        $alert = static::create($attributes + [
            'type' => $type,
            'severity' => $severity,
            'status' => AlertStatus::New,
            'description' => $description,
            'happened_at' => now(),
        ]);

        // Fan out an in-app notification to the active security staff,
        // honoring each user's preferences. rescue(): a notification
        // failure must never break the action that raised the alert.
        rescue(fn () => Notification::send(
            User::where('status', UserStatus::Active)
                ->whereIn('role', [UserRole::Administrator, UserRole::SecurityOfficer])
                ->get()
                ->filter(fn (User $user) => $user->wantsAlertNotification($severity)),
            new SecurityAlert($alert)
        ), null, false);

        return $alert;
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope: alerts still needing attention.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [AlertStatus::New, AlertStatus::Pending, AlertStatus::Investigating]);
    }

    /**
     * Scope: raised today.
     */
    public function scopeToday(Builder $query): Builder
    {
        // Range instead of whereDate() so the happened_at index is used.
        return $query->whereBetween('happened_at', [today(), today()->endOfDay()]);
    }

    /**
     * Scope: search by code, type or description.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('alert_code', 'like', "%{$term}%")
                    ->orWhere('type', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Where the alert came from, for the table's Location column.
     */
    public function locationLabel(): string
    {
        return $this->door?->name
            ?? $this->camera?->location
            ?? $this->device?->placement()
            ?? trim(($this->building ?? '').' '.($this->floor ?? '')) ?: 'Facility';
    }
}
