<?php

namespace App\Modules\Catalog\Models;

use App\Models\User;
use App\Modules\Accounts\Models\SavedVehicle;
use App\Modules\Catalog\Enums\VehicleCondition;
use App\Modules\Catalog\Enums\VehicleSource;
use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Leads\Models\Enquiry;
use App\Modules\Settings\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'user_id',
        'source',
        'make_id',
        'model_id',
        'body_type_id',
        'fuel_type_id',
        'transmission_id',
        'exterior_color_id',
        'interior_color_id',
        'trim',
        'year',
        'mileage_km',
        'price',
        'condition',
        'description',
        'vin',
        'registration_number',
        'owners_count',
        'accident_history',
        'warranty',
        'last_service_date',
        'finance_deposit',
        'finance_term_months',
        'finance_apr',
        'phone',
        'whatsapp',
        'phone_display',
        'location',
        'status',
        'published_at',
        'is_featured',
        'views_count',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'source' => VehicleSource::class,
        'condition' => VehicleCondition::class,
        'status' => VehicleStatus::class,
        'price' => 'decimal:2',
        'finance_deposit' => 'decimal:2',
        'finance_apr' => 'decimal:2',
        'year' => 'integer',
        'mileage_km' => 'integer',
        'owners_count' => 'integer',
        'finance_term_months' => 'integer',
        'views_count' => 'integer',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'last_service_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $vehicle) {
            if (blank($vehicle->slug)) {
                $vehicle->slug = static::uniqueVehicleSlug($vehicle);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(Make::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function bodyType(): BelongsTo
    {
        return $this->belongsTo(BodyType::class);
    }

    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(Transmission::class);
    }

    public function exteriorColor(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'exterior_color_id');
    }

    public function interiorColor(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'interior_color_id');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'vehicle_features')->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(VehicleImage::class)->where('is_cover', true)->orderBy('sort_order');
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function savedBy(): HasMany
    {
        return $this->hasMany(SavedVehicle::class);
    }

    protected function contactPhone(): Attribute
    {
        return Attribute::get(fn (): string => (string) ($this->phone ?: Setting::get('contact.phone', '')));
    }

    protected function contactWhatsApp(): Attribute
    {
        return Attribute::get(fn (): string => (string) ($this->whatsapp ?: Setting::get('contact.whatsapp', $this->contactPhone)));
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn (): string => 'LKR '.number_format((float) $this->price, 0));
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [VehicleStatus::Published, VehicleStatus::Sold])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->publiclyVisible()->where('is_featured', true);
    }

    public function scopeFilter(Builder $query, array $filters = []): Builder
    {
        $query->publiclyVisible();

        foreach (['make_id', 'model_id', 'body_type_id', 'fuel_type_id', 'transmission_id', 'exterior_color_id'] as $column) {
            if (filled($filters[$column] ?? null)) {
                $query->where('vehicles.'.$column, $filters[$column]);
            }
        }

        if (filled($filters['condition'] ?? null)) {
            $query->where('condition', $filters['condition']);
        }

        if (filled($filters['location'] ?? null)) {
            $query->where('location', $filters['location']);
        }

        if (isset($filters['min_year']) && $filters['min_year'] !== '') {
            $query->where('year', '>=', (int) $filters['min_year']);
        }

        if (isset($filters['max_year']) && $filters['max_year'] !== '') {
            $query->where('year', '<=', (int) $filters['max_year']);
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (isset($filters['max_mileage']) && $filters['max_mileage'] !== '') {
            $query->where('mileage_km', '<=', (int) $filters['max_mileage']);
        }

        if (filled($filters['featured'] ?? null)) {
            $query->where('is_featured', true);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));

        if ($keyword !== '') {
            $like = '%'.$keyword.'%';

            $query->where(function (Builder $q) use ($like) {
                $q->where('vehicles.trim', 'like', $like)
                    ->orWhere('vehicles.description', 'like', $like)
                    ->orWhere('vehicles.registration_number', 'like', $like)
                    ->orWhere('vehicles.location', 'like', $like)
                    ->orWhereHas('make', fn (Builder $m) => $m->where('name', 'like', $like))
                    ->orWhereHas('model', fn (Builder $m) => $m->where('name', 'like', $like));
            });
        }

        $query->with(['make', 'model', 'bodyType', 'fuelType', 'transmission', 'coverImage']);

        match ($filters['sort'] ?? 'latest') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'year_desc' => $query->orderByDesc('year'),
            'year_asc' => $query->orderBy('year'),
            'mileage_asc' => $query->orderBy('mileage_km'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('published_at'),
        };

        return $query;
    }

    public function incrementViews(): void
    {
        static::withoutTouching(function () {
            static::withoutEvents(function () {
                static::whereKey($this->getKey())->increment('views_count');
            });

            $this->views_count++;
        });
    }

    public function isPublished(): bool
    {
        return $this->status === VehicleStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function isSold(): bool
    {
        return $this->status === VehicleStatus::Sold;
    }

    private static function uniqueVehicleSlug(self $vehicle): string
    {
        $prefix = collect([
            $vehicle->make?->name,
            $vehicle->model?->name,
            $vehicle->year,
        ])->filter()->implode(' ');

        $base = Str::slug($prefix) ?: 'vehicle';
        $slug = $base;
        $suffix = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
