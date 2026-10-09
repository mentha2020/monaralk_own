<?php

namespace App\Modules\Catalog\Observers;

use App\Modules\Catalog\Support\CatalogCache;
use Illuminate\Database\Eloquent\Model;

class CatalogCacheObserver
{
    public function saved(Model $model): void
    {
        CatalogCache::invalidate();
    }

    public function deleted(Model $model): void
    {
        CatalogCache::invalidate();
    }
}
