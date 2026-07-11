<?php

namespace App\Models;

use App\Enums\VisitAccessLevel;
use App\Enums\VisitDocumentType;
use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Visit extends Model
{
    /** @use HasFactory<\Database\Factories\VisitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'visit_code',
        'full_name',
        'national_id',
        'phone',
        'email',
        'gender',
        'date_of_birth',
        'nationality',
        'photo',
        'company',
        'host_user_id',
        'department',
        'purpose',
        'visit_date',
        'expected_check_in',
        'expected_duration_minutes',
        'companions',
        'vehicle_plate',
        'document_type',
        'badge_number',
        'bag_inspected',
        'special_permission',
        'access_level',
        'blacklisted',
        'security_notes',
        'checked_in_at',
        'checked_out_at',
        'status',
        'registered_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'visit_date' => 'date',
            'expected_duration_minutes' => 'integer',
            'companions' => 'integer',
            'document_type' => VisitDocumentType::class,
            'bag_inspected' => 'boolean',
            'special_permission' => 'boolean',
            'access_level' => VisitAccessLevel::class,
            'blacklisted' => 'boolean',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'status' => VisitStatus::class,
        ];
    }

    /**
     * Assign a sequential visit code once the row exists.
     */
    protected static function booted(): void
    {
        static::created(function (Visit $visit) {
            if (! $visit->visit_code) {
                $visit->visit_code = 'VST-'.str_pad((string) $visit->id, 5, '0', STR_PAD_LEFT);
                $visit->saveQuietly();
            }
        });
    }

    /**
     * The employee being visited.
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    /**
     * The reception/security employee who registered the visit.
     */
    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Scope: search by visitor name, national ID, company or visit code.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('full_name', 'like', "%{$term}%")
                    ->orWhere('national_id', 'like', "%{$term}%")
                    ->orWhere('company', 'like', "%{$term}%")
                    ->orWhere('visit_code', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Scope: visitors currently inside the building.
     */
    public function scopeInside(Builder $query): Builder
    {
        return $query->where('status', VisitStatus::Inside);
    }

    /**
     * Scope: visits scheduled for today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('visit_date', today());
    }

    /**
     * Visitor initials shown when no photo was uploaded.
     */
    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $parts = preg_split('/\s+/', trim($this->full_name)) ?: [];

            return strtoupper(
                mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1)
            );
        });
    }

    /**
     * Public URL of the visitor photo, or null when none was uploaded.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo ? Storage::url($this->photo) : null);
    }

    /**
     * Actual visit duration in minutes (in-progress visits count up to now).
     */
    public function durationMinutes(): ?int
    {
        if (! $this->checked_in_at) {
            return null;
        }

        return (int) $this->checked_in_at->diffInMinutes($this->checked_out_at ?? now());
    }

    /**
     * Human-readable duration, e.g. "1h 25m", or null before check-in.
     */
    public function durationLabel(): ?string
    {
        $minutes = $this->durationMinutes();

        if ($minutes === null) {
            return null;
        }

        return $minutes >= 60
            ? intdiv($minutes, 60).'h '.($minutes % 60).'m'
            : $minutes.'m';
    }

    /**
     * When the visitor is expected to leave, based on check-in time.
     */
    public function expectedEndAt(): ?\Illuminate\Support\Carbon
    {
        return $this->checked_in_at?->copy()->addMinutes($this->expected_duration_minutes);
    }

    /**
     * Still inside past the allowed visit duration.
     */
    public function isOverstay(): bool
    {
        return $this->status === VisitStatus::Inside
            && $this->expectedEndAt() !== null
            && $this->expectedEndAt()->isPast();
    }

    /**
     * Checked in on a previous day and never checked out.
     */
    public function forgotCheckOut(): bool
    {
        return $this->status === VisitStatus::Inside
            && $this->checked_in_at !== null
            && ! $this->checked_in_at->isToday();
    }

    /**
     * Earlier visits by the same person (matched by national ID).
     */
    public function previousVisits(): Builder
    {
        return static::query()
            ->where('national_id', $this->national_id)
            ->whereKeyNot($this->getKey())
            ->orderByDesc('visit_date');
    }
}
