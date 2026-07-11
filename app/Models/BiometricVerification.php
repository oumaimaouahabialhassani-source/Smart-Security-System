<?php

namespace App\Models;

use App\Enums\BiometricMethod;
use App\Enums\BiometricResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricVerification extends Model
{
    /** @use HasFactory<\Database\Factories\BiometricVerificationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'biometric_profile_id',
        'subject_name',
        'method',
        'device_id',
        'result',
        'detail',
        'duration_ms',
        'ip_address',
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
            'method' => BiometricMethod::class,
            'result' => BiometricResult::class,
            'duration_ms' => 'integer',
            'happened_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(BiometricProfile::class, 'biometric_profile_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Scope: attempts recorded today.
     */
    public function scopeToday(Builder $query): Builder
    {
        // Range instead of whereDate() so the happened_at index is used.
        return $query->whereBetween('happened_at', [today(), today()->endOfDay()]);
    }

    /**
     * Scope: search by subject name or detail.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('subject_name', 'like', "%{$term}%")
                    ->orWhere('detail', 'like', "%{$term}%");
            });
        });
    }
}
