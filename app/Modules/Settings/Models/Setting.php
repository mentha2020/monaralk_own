<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group', 'type'];

    protected $casts = ['type' => 'string'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = static::allCached();

        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        return static::castValue($settings[$key]['value'], $settings[$key]['type']);
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => static::serializeValue($value), 'group' => $group, 'type' => $type]
        );

        Cache::forget(static::cacheKey());

        return $setting;
    }

    public static function allCached(): array
    {
        return Cache::rememberForever(static::cacheKey(), function () {
            return static::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn (self $s) => [$s->key => ['value' => $s->value, 'type' => $s->type]])
                ->all();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(static::cacheKey());
    }

    protected static function cacheKey(): string
    {
        return 'settings.all';
    }

    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            default => $value,
        };
    }

    private static function serializeValue(mixed $value): ?string
    {
        return $value === null ? null : (is_scalar($value) ? (string) $value : json_encode($value));
    }
}
