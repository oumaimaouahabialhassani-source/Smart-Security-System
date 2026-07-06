<?php

namespace App\Models;

use App\Enums\DeviceProtocol;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\SignalStrength;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory;

    /**
     * Battery percentage at or below which a device counts as "low battery".
     */
    public const LOW_BATTERY = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'device_id',
        'name',
        'type',
        'brand',
        'model',
        'protocol',
        'ip_address',
        'mac_address',
        'serial_number',
        'firmware_version',
        'username',
        'password',
        'building',
        'floor',
        'zone',
        'room',
        'battery_level',
        'signal_strength',
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
            'type' => DeviceType::class,
            'protocol' => DeviceProtocol::class,
            'status' => DeviceStatus::class,
            'signal_strength' => SignalStrength::class,
            'password' => 'encrypted',
            'battery_level' => 'integer',
            'last_seen' => 'datetime',
        ];
    }

    /**
     * Scope: search by name, device ID or IP address.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('device_id', 'like', "%{$term}%")
                    ->orWhere('ip_address', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Scope: online devices.
     */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', DeviceStatus::Online);
    }

    /**
     * Scope: battery at or below the low-battery threshold.
     */
    public function scopeLowBattery(Builder $query): Builder
    {
        return $query->whereNotNull('battery_level')
            ->where('battery_level', '<=', self::LOW_BATTERY);
    }

    /**
     * CSS tone for the battery indicator: ok / warn / danger.
     */
    public function batteryTone(): string
    {
        return match (true) {
            $this->battery_level === null => 'ok',
            $this->battery_level <= self::LOW_BATTERY => 'danger',
            $this->battery_level <= 60 => 'warn',
            default => 'ok',
        };
    }

    /**
     * Active alert labels for this device (derived, placeholder logic).
     *
     * @return list<string>
     */
    public function activeAlerts(): array
    {
        $alerts = [];

        if ($this->status === DeviceStatus::Offline) {
            $alerts[] = 'Device Offline';
        }

        if ($this->battery_level !== null && $this->battery_level <= self::LOW_BATTERY) {
            $alerts[] = 'Battery Low';
        }

        if ($this->signal_strength === SignalStrength::Weak) {
            $alerts[] = 'Signal Weak';
        }

        return $alerts;
    }

    /**
     * Full placement, e.g. "HQ Building A — Floor 2 — Zone North".
     */
    public function placement(): string
    {
        return "{$this->building} — {$this->floor} — {$this->zone}".($this->room ? " — {$this->room}" : '');
    }
}
