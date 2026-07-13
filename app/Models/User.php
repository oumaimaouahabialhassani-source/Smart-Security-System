<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'avatar',
        'role',
        'status',
        'last_login',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'last_login' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Full name, kept so existing auth()->user()->name calls work.
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * Initials shown when the user has no avatar image.
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn () => strtoupper(
            mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1)
        ));
    }

    /**
     * Public URL of the avatar image, or null when none was uploaded.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar ? Storage::url($this->avatar) : null);
    }

    /**
     * The user's biometric enrollment profile, if any.
     */
    public function biometricProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BiometricProfile::class);
    }

    /**
     * The account that created this user.
     */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    /**
     * The account that last edited this user.
     */
    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    /**
     * Should this user receive an in-app notification for an alert of
     * the given severity? Honors the "critical only" preference saved
     * from the Alerts notification settings panel.
     */
    public function wantsAlertNotification(\App\Enums\AlertSeverity $severity): bool
    {
        $preferences = $this->notification_preferences ?? [];

        if (($preferences['critical_only'] ?? false)
            && ! in_array($severity, [\App\Enums\AlertSeverity::Critical, \App\Enums\AlertSeverity::High], true)) {
            return false;
        }

        return true;
    }

    /**
     * Scope: search by name or email.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhereRaw("concat(first_name, ' ', last_name) like ?", ["%{$term}%"])
                    ->orWhere('email', 'like', "%{$term}%");
            });
        });
    }
}
