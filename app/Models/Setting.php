<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['group', 'key', 'value'];

    /**
     * Read a setting: Setting::get('security.password_min_length', 8).
     * Values are cached for the whole request cycle and across
     * requests until a setting is saved.
     */
    public static function get(string $dottedKey, mixed $default = null): mixed
    {
        [$group, $key] = self::split($dottedKey);

        return self::all_cached()["{$group}.{$key}"] ?? $default;
    }

    /**
     * Write a setting: Setting::set('general.company_name', 'ACME').
     */
    public static function set(string $dottedKey, mixed $value): void
    {
        [$group, $key] = self::split($dottedKey);

        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => json_encode($value)],
        );

        Cache::forget('settings.all');
    }

    /**
     * Every value of a group, without the group prefix.
     *
     * @return array<string, mixed>
     */
    public static function group(string $group): array
    {
        $values = [];

        foreach (self::all_cached() as $dotted => $value) {
            if (str_starts_with($dotted, "{$group}.")) {
                $values[substr($dotted, strlen($group) + 1)] = $value;
            }
        }

        return $values;
    }

    /**
     * All settings as a flat "group.key" => value map.
     *
     * @return array<string, mixed>
     */
    private static function all_cached(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return static::query()->get()
                ->mapWithKeys(fn (Setting $s) => ["{$s->group}.{$s->key}" => json_decode($s->value, true)])
                ->all();
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function split(string $dottedKey): array
    {
        $pos = strpos($dottedKey, '.');

        return [substr($dottedKey, 0, $pos), substr($dottedKey, $pos + 1)];
    }
}
