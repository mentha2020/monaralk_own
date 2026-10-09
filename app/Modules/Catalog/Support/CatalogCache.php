<?php

namespace App\Modules\Catalog\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class CatalogCache
{
    public const VERSION_KEY = 'catalog.cache.version';

    public static function remember(string $segment, array $payload, Closure $miss): mixed
    {
        if (! static::enabled()) {
            return $miss();
        }

        return Cache::remember(
            static::key($segment, $payload),
            now()->addSeconds((int) config('catalog.cache.ttl', 300)),
            $miss
        );
    }

    public static function rememberForever(string $segment, array $payload, Closure $miss): mixed
    {
        if (! static::enabled()) {
            return $miss();
        }

        return Cache::rememberForever(static::key($segment, $payload), $miss);
    }

    public static function invalidate(): void
    {
        Cache::forever(static::VERSION_KEY, uniqid('v', true));
    }

    public static function enabled(): bool
    {
        return (bool) config('catalog.cache.enabled', false);
    }

    private static function key(string $segment, array $payload): string
    {
        return 'catalog.'.$segment.'.'.static::version().'.'.md5(json_encode($payload));
    }

    private static function version(): string
    {
        $version = Cache::get(static::VERSION_KEY);

        if (! is_string($version)) {
            $version = uniqid('v', true);

            Cache::forever(static::VERSION_KEY, $version);
        }

        return $version;
    }
}
