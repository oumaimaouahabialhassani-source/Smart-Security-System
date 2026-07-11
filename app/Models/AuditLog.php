<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'module',
        'action',
        'description',
        'status',
        'ip_address',
        'browser',
        'operating_system',
        'device_type',
        'user_agent',
        'url',
        'http_method',
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
            'happened_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Write an audit entry, capturing the current user and request
     * context automatically. Console runs (seeding, scheduled jobs)
     * are skipped except during tests.
     */
    public static function record(string $module, string $action, string $description, string $status = 'success', ?User $actor = null): ?self
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        $actor ??= auth()->user();
        $agent = request()->userAgent();

        return static::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name,
            'user_role' => $actor?->role?->value,
            'module' => $module,
            'action' => $action,
            'description' => \Illuminate\Support\Str::limit($description, 480),
            'status' => $status,
            'ip_address' => request()->ip(),
            'browser' => self::parseBrowser($agent),
            'operating_system' => self::parseOs($agent),
            'device_type' => self::parseDevice($agent),
            'user_agent' => $agent,
            'url' => \Illuminate\Support\Str::limit(request()->fullUrl(), 480),
            'http_method' => request()->method(),
            'happened_at' => now(),
        ]);
    }

    /**
     * Scope: entries recorded today.
     */
    public function scopeToday(Builder $query): Builder
    {
        // Range instead of whereDate() so the happened_at index is used.
        return $query->whereBetween('happened_at', [today(), today()->endOfDay()]);
    }

    /**
     * Scope: search by user, action, description or IP.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('user_name', 'like', "%{$term}%")
                    ->orWhere('action', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('ip_address', 'like', "%{$term}%");
            });
        });
    }

    private static function parseBrowser(?string $agent): ?string
    {
        return match (true) {
            $agent === null => null,
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Other',
        };
    }

    private static function parseOs(?string $agent): ?string
    {
        return match (true) {
            $agent === null => null,
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Other',
        };
    }

    private static function parseDevice(?string $agent): ?string
    {
        return match (true) {
            $agent === null => null,
            str_contains($agent, 'iPad'), str_contains($agent, 'Tablet') => 'Tablet',
            str_contains($agent, 'Mobile'), str_contains($agent, 'iPhone'), str_contains($agent, 'Android') => 'Mobile',
            default => 'Desktop',
        };
    }
}
