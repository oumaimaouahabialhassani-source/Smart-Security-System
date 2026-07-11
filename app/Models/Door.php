<?php

namespace App\Models;

use App\Enums\AccessLevel;
use App\Enums\DoorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Door extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'building',
        'floor',
        'device_id',
        'camera_id',
        'required_access_level',
        'status',
        'last_activity_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_access_level' => AccessLevel::class,
            'status' => DoorStatus::class,
            'last_activity_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AccessPermission::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AccessEvent::class);
    }
}
