<?php

namespace App\Modules\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function (Model $model) {
            $source = $model->name ?? $model->title ?? null;

            if (blank($model->slug) && filled($source)) {
                $model->slug = static::uniqueSlug((string) $source);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug ?: 'item';
    }
}
