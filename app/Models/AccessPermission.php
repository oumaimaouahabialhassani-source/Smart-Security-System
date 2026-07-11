<?php

namespace App\Models;

use App\Enums\AccessLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccessPermission extends Model
{
    /** @use HasFactory<\Database\Factories\AccessPermissionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'visitor_name',
        'company',
        'host_user_id',
        'badge_id',
        'department',
        'position',
        'access_level',
        'building',
        'floor',
        'working_days',
        'start_time',
        'end_time',
        'valid_from',
        'valid_until',
        'notes',
        'active',
        'type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_level' => AccessLevel::class,
            'working_days' => 'array',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function doors(): BelongsToMany
    {
        return $this->belongsToMany(Door::class);
    }

    /**
     * Display name: the employee, or the visitor on temporary passes.
     */
    public function holderName(): string
    {
        return $this->user?->name ?? $this->visitor_name ?? 'Unknown';
    }

    /**
     * Scope: search by holder name, badge ID or company.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('badge_id', 'like', "%{$term}%")
                    ->orWhere('visitor_name', 'like', "%{$term}%")
                    ->orWhere('company', 'like', "%{$term}%")
                    ->orWhereHas('user', function (Builder $query) use ($term) {
                        $query->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhereRaw("concat(first_name, ' ', last_name) like ?", ["%{$term}%"]);
                    });
            });
        });
    }

    /**
     * Expired, disabled or not-yet-valid permissions cannot open doors.
     */
    public function isCurrentlyValid(): bool
    {
        return $this->active
            && $this->valid_from->lte(today())
            && ($this->valid_until === null || $this->valid_until->gte(today()));
    }

    /**
     * Human-readable working schedule, e.g. "Mon–Fri · 08:00–18:00".
     */
    public function scheduleLabel(): string
    {
        $days = collect($this->working_days ?? [])->map(fn ($d) => substr($d, 0, 3))->implode(', ');
        $hours = $this->start_time && $this->end_time
            ? substr($this->start_time, 0, 5).'–'.substr($this->end_time, 0, 5)
            : 'All hours';

        return ($days !== '' ? $days : 'All days').' · '.$hours;
    }
}
