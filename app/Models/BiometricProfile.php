<?php

namespace App\Models;

use App\Enums\BiometricStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BiometricProfile extends Model
{
    /** @use HasFactory<\Database\Factories\BiometricProfileFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'employee_code',
        'department',
        'position',
        'face_enrolled_at',
        'face_quality',
        'fingerprint_enrolled_at',
        'fingerprint_finger',
        'fingerprint_quality',
        'iris_enrolled_at',
        'assigned_device_id',
        'status',
        'security_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'face_enrolled_at' => 'datetime',
            'face_quality' => 'integer',
            'fingerprint_enrolled_at' => 'datetime',
            'fingerprint_quality' => 'integer',
            'iris_enrolled_at' => 'datetime',
            'status' => BiometricStatus::class,
        ];
    }

    /**
     * Assign a sequential employee code once the row exists.
     */
    protected static function booted(): void
    {
        static::created(function (BiometricProfile $profile) {
            if (! $profile->employee_code) {
                $profile->employee_code = 'EMP-'.str_pad((string) $profile->id, 4, '0', STR_PAD_LEFT);
                $profile->saveQuietly();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'assigned_device_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(BiometricVerification::class);
    }

    /**
     * Most recent verification attempt, for the table's
     * "Last Verification" column without N+1 queries.
     */
    public function latestVerification(): HasOne
    {
        return $this->hasOne(BiometricVerification::class)->latestOfMany('happened_at');
    }

    /**
     * Scope: search by employee name, email or employee code.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('employee_code', 'like', "%{$term}%")
                    ->orWhereHas('user', function (Builder $query) use ($term) {
                        $query->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhereRaw("concat(first_name, ' ', last_name) like ?", ["%{$term}%"])
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        });
    }

    /**
     * Number of enrolled modalities (0-3).
     */
    public function enrolledCount(): int
    {
        return (int) (bool) $this->face_enrolled_at
            + (int) (bool) $this->fingerprint_enrolled_at
            + (int) (bool) $this->iris_enrolled_at;
    }
}
