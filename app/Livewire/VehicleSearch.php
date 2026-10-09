<?php

namespace App\Livewire;

use App\Modules\Catalog\Enums\VehicleCondition;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleModel;
use App\Modules\Catalog\Support\CatalogCache;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class VehicleSearch extends Component
{
    use WithPagination;

    public const PER_PAGE = 12;

    public const SORTS = [
        'latest' => 'Newest listed',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'year_desc' => 'Year: newest first',
        'year_asc' => 'Year: oldest first',
        'mileage_asc' => 'Mileage: lowest first',
        'newest' => 'Recently added',
    ];

    #[Url(except: '')]
    public string $keyword = '';

    #[Url(except: '')]
    public string $make_id = '';

    #[Url(except: '')]
    public string $model_id = '';

    #[Url(except: '')]
    public string $body_type_id = '';

    #[Url(except: '')]
    public string $fuel_type_id = '';

    #[Url(except: '')]
    public string $transmission_id = '';

    #[Url(except: '')]
    public string $exterior_color_id = '';

    #[Url(except: '')]
    public string $condition = '';

    #[Url(except: '')]
    public string $location = '';

    #[Url(except: '')]
    public string $min_year = '';

    #[Url(except: '')]
    public string $max_year = '';

    #[Url(except: '')]
    public string $min_price = '';

    #[Url(except: '')]
    public string $max_price = '';

    #[Url(except: '')]
    public string $max_mileage = '';

    #[Url(except: false)]
    public bool $featured = false;

    #[Url(as: 'sort', except: 'latest')]
    public string $sort = 'latest';

    public bool $showFilters = false;

    public function updated(string $property): void
    {
        if ($property !== 'showFilters') {
            $this->resetPage();
        }
    }

    public function updatedMakeId(): void
    {
        $this->model_id = '';
    }

    public function clearFilter(string $property): void
    {
        if (property_exists($this, $property) && $property !== 'sort') {
            $this->{$property} = '';
            $this->resetPage();
        }
    }

    public function clearAll(): void
    {
        foreach ($this->filterProperties() as $property) {
            $this->{$property} = '';
        }

        $this->sort = 'latest';
        $this->resetPage();
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function render(): View
    {
        return view('livewire.vehicle-search', [
            'vehicles' => $this->vehicles(),
            'makes' => CatalogCache::rememberForever('options.makes', [], fn (): Collection => Make::query()->where('is_active', true)->orderBy('name')->get()),
            'models' => $this->modelOptions(),
            'bodyTypes' => CatalogCache::rememberForever('options.body_types', [], fn (): Collection => BodyType::query()->where('is_active', true)->orderBy('name')->get()),
            'fuelTypes' => CatalogCache::rememberForever('options.fuel_types', [], fn (): Collection => FuelType::query()->where('is_active', true)->orderBy('name')->get()),
            'transmissions' => CatalogCache::rememberForever('options.transmissions', [], fn (): Collection => Transmission::query()->where('is_active', true)->orderBy('name')->get()),
            'colors' => CatalogCache::rememberForever('options.colors', [], fn (): Collection => Color::query()->where('is_active', true)->orderBy('name')->get()),
            'conditions' => VehicleCondition::options(),
            'locations' => $this->locationOptions(),
            'yearBounds' => $this->yearBounds(),
            'sorts' => self::SORTS,
            'activeFilters' => $this->activeFilters(),
        ]);
    }

    public function vehicles(): LengthAwarePaginator
    {
        $filters = $this->filters();
        $page = max(1, (int) $this->getPage());

        $result = CatalogCache::remember(
            'search',
            array_merge($filters, ['page' => $page]),
            fn (): LengthAwarePaginator => Vehicle::query()
                ->filter($filters)
                ->paginate(self::PER_PAGE, ['*'], 'page', $page)
        );

        return $result->withQueryString();
    }

    public function filters(): array
    {
        return [
            'keyword' => $this->keyword,
            'make_id' => $this->make_id,
            'model_id' => $this->model_id,
            'body_type_id' => $this->body_type_id,
            'fuel_type_id' => $this->fuel_type_id,
            'transmission_id' => $this->transmission_id,
            'exterior_color_id' => $this->exterior_color_id,
            'condition' => $this->condition,
            'location' => $this->location,
            'min_year' => $this->min_year,
            'max_year' => $this->max_year,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'max_mileage' => $this->max_mileage,
            'featured' => $this->featured,
            'sort' => $this->sort,
        ];
    }

    public function activeFilters(): array
    {
        $relationals = [
            'make_id' => ['Make', Make::class],
            'model_id' => ['Model', VehicleModel::class],
            'body_type_id' => ['Body type', BodyType::class],
            'fuel_type_id' => ['Fuel', FuelType::class],
            'transmission_id' => ['Transmission', Transmission::class],
            'exterior_color_id' => ['Colour', Color::class],
        ];

        $chips = [];

        if (filled($this->keyword)) {
            $chips[] = ['property' => 'keyword', 'label' => 'Keyword', 'value' => $this->keyword];
        }

        foreach ($relationals as $property => [$label, $model]) {
            if (filled($this->{$property})) {
                $name = $model::query()->whereKey((int) $this->{$property})->value('name');

                $chips[] = [
                    'property' => $property,
                    'label' => $label,
                    'value' => filled($name) ? $name : '#'.$this->{$property},
                ];
            }
        }

        if (filled($this->condition)) {
            $chips[] = ['property' => 'condition', 'label' => 'Condition', 'value' => VehicleCondition::tryFrom($this->condition)?->label() ?? $this->condition];
        }

        if (filled($this->location)) {
            $chips[] = ['property' => 'location', 'label' => 'Location', 'value' => $this->location];
        }

        $ranges = [
            'min_year' => fn (): string => 'Year from '.$this->min_year,
            'max_year' => fn (): string => 'Year to '.$this->max_year,
            'min_price' => fn (): string => 'From LKR '.number_format((float) $this->min_price),
            'max_price' => fn (): string => 'To LKR '.number_format((float) $this->max_price),
            'max_mileage' => fn (): string => 'Under '.number_format((int) $this->max_mileage).' km',
        ];

        foreach ($ranges as $property => $format) {
            if (isset($this->{$property}) && $this->{$property} !== '') {
                $chips[] = ['property' => $property, 'label' => 'Filter', 'value' => $format()];
            }
        }

        if ($this->featured) {
            $chips[] = ['property' => 'featured', 'label' => 'Filter', 'value' => 'Featured only'];
        }

        return $chips;
    }

    private function filterProperties(): array
    {
        return [
            'keyword', 'make_id', 'model_id', 'body_type_id', 'fuel_type_id', 'transmission_id',
            'exterior_color_id', 'condition', 'location', 'min_year', 'max_year', 'min_price',
            'max_price', 'max_mileage', 'featured',
        ];
    }

    private function modelOptions(): Collection
    {
        return CatalogCache::rememberForever('options.models', ['make_id' => $this->make_id], function (): Collection {
            $query = VehicleModel::query()->where('is_active', true);

            if (filled($this->make_id)) {
                $query->where('make_id', (int) $this->make_id);
            }

            return $query->orderBy('name')->get();
        });
    }

    private function locationOptions(): SupportCollection
    {
        return CatalogCache::rememberForever('options.locations', [], function (): SupportCollection {
            return Vehicle::query()
                ->publiclyVisible()
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->orderBy('location')
                ->pluck('location');
        });
    }

    private function yearBounds(): array
    {
        return CatalogCache::rememberForever('options.year_bounds', [], function (): array {
            $bounds = Vehicle::query()
                ->publiclyVisible()
                ->selectRaw('MIN(year) as min_year, MAX(year) as max_year')
                ->first();

            return [
                'min' => (int) ($bounds->min_year ?? 1990),
                'max' => (int) ($bounds->max_year ?? now()->year),
            ];
        });
    }
}
