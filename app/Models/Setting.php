<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type'];

    /**
     * Read a setting (cached). Falls back to env/$default when missing or blank.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever("setting:$key", function () use ($key) {
            $row = static::query()->where('key', $key)->first();
            return $row ? ['v' => $row->value, 't' => $row->type] : null;
        });

        if ($value === null || $value['v'] === null || $value['v'] === '') {
            return $default;
        }

        return static::castValue($value['v'], $value['t']);
    }

    public static function put(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        $stored = match (true) {
            $type === 'json'           => json_encode($value),
            is_bool($value)            => $value ? '1' : '0',
            default                    => (string) $value,
        };

        static::updateOrCreate(['key' => $key], [
            'value' => $stored,
            'group' => $group,
            'type'  => $type,
        ]);

        Cache::forget("setting:$key");
    }

    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'bool'  => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int'   => (int) $value,
            'json'  => json_decode($value, true),
            default => $value, // string|secret
        };
    }

    /** All settings in a group as key=>casted value, for admin forms. */
    public static function group(string $group): array
    {
        return static::where('group', $group)->get()
            ->mapWithKeys(fn ($s) => [$s->key => static::castValue($s->value, $s->type)])
            ->all();
    }
}
