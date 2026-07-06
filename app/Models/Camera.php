<?php

namespace App\Models;

use App\Enums\CameraBrand;
use App\Enums\CameraStatus;
use App\Enums\CameraType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    /** @use HasFactory<\Database\Factories\CameraFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'camera_id',
        'name',
        'brand',
        'model',
        'type',
        'ip_address',
        'mac_address',
        'username',
        'password',
        'rtsp_url',
        'location',
        'building',
        'floor',
        'zone',
        'resolution',
        'fps',
        'recording_enabled',
        'status',
        'last_seen',
        'description',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'brand' => CameraBrand::class,
            'type' => CameraType::class,
            'status' => CameraStatus::class,
            'password' => 'encrypted',
            'recording_enabled' => 'boolean',
            'fps' => 'integer',
            'last_seen' => 'datetime',
        ];
    }

    /**
     * Scope: search by name, camera ID, IP address or location.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('camera_id', 'like', "%{$term}%")
                    ->orWhere('ip_address', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Scope: online cameras.
     */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', CameraStatus::Online);
    }

    /**
     * Full placement, e.g. "HQ Building A — Floor 2 — Zone North".
     */
    public function placement(): string
    {
        return "{$this->building} — {$this->floor} — {$this->zone}";
    }
}
