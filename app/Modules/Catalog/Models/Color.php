<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Shared\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['name', 'slug', 'hex', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function exteriorVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'exterior_color_id');
    }

    public function interiorVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'interior_color_id');
    }
}
