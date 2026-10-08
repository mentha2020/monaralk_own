<?php

namespace App\Modules\Catalog\Exports;

use App\Modules\Catalog\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VehicleExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public const COLUMNS = [
        'slug',
        'make',
        'model',
        'body_type',
        'fuel_type',
        'transmission',
        'exterior_color',
        'interior_color',
        'trim',
        'year',
        'mileage_km',
        'price',
        'condition',
        'status',
        'is_featured',
        'location',
        'vin',
        'registration_number',
        'description',
        'published_at',
    ];

    public function query(): Builder
    {
        return Vehicle::query()->with([
            'make',
            'model',
            'bodyType',
            'fuelType',
            'transmission',
            'exteriorColor',
            'interiorColor',
        ]);
    }

    public function headings(): array
    {
        return self::COLUMNS;
    }

    public function map($row): array
    {
        /** @var Vehicle $row */
        return [
            'slug' => $row->slug,
            'make' => $row->make?->name,
            'model' => $row->model?->name,
            'body_type' => $row->bodyType?->name,
            'fuel_type' => $row->fuelType?->name,
            'transmission' => $row->transmission?->name,
            'exterior_color' => $row->exteriorColor?->name,
            'interior_color' => $row->interiorColor?->name,
            'trim' => $row->trim,
            'year' => $row->year,
            'mileage_km' => $row->mileage_km,
            'price' => $row->price,
            'condition' => $row->condition->value,
            'status' => $row->status->value,
            'is_featured' => $row->is_featured ? 1 : 0,
            'location' => $row->location,
            'vin' => $row->vin,
            'registration_number' => $row->registration_number,
            'description' => $row->description,
            'published_at' => $row->published_at?->format(Carbon::ATOM),
        ];
    }
}
