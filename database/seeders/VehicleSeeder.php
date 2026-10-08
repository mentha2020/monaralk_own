<?php

namespace Database\Seeders;

use App\Modules\Catalog\Enums\VehicleCondition;
use App\Modules\Catalog\Enums\VehicleSource;
use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Feature;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use App\Modules\Catalog\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleSeeder extends Seeder
{
    private const PLAN = [
        ['Toyota', 'Corolla', 2019, 8_950_000, 62_400, 'Sedan', 'Hybrid', 'Automatic', true],
        ['Toyota', 'Aqua', 2018, 7_450_000, 48_900, 'Hatchback', 'Hybrid', 'CVT', false],
        ['Honda', 'Vezel', 2020, 14_250_000, 39_500, 'SUV', 'Hybrid', 'Automatic', true],
        ['Honda', 'Civic', 2021, 16_900_000, 28_300, 'Sedan', 'Petrol', 'CVT', false],
        ['Suzuki', 'Alto', 2022, 3_650_000, 18_700, 'Hatchback', 'Petrol', 'Manual', false],
        ['Suzuki', 'Wagon R', 2017, 4_150_000, 71_200, 'Hatchback', 'Petrol', 'Manual', false],
        ['Nissan', 'X-Trail', 2019, 12_400_000, 55_600, 'SUV', 'Hybrid', 'CVT', true],
        ['Mazda', 'CX-5', 2021, 17_850_000, 31_100, 'SUV', 'Petrol', 'Automatic', false],
        ['Mitsubishi', 'Lancer', 2016, 6_750_000, 94_300, 'Sedan', 'Petrol', 'Manual', false],
        ['Kia', 'Sportage', 2022, 19_500_000, 22_800, 'SUV', 'Diesel', 'Automatic', true],
        ['BMW', '3 Series', 2020, 24_900_000, 41_600, 'Sedan', 'Petrol', 'Automatic', false],
        ['Perodua', 'Axia', 2023, 4_950_000, 9_400, 'Hatchback', 'Petrol', 'Manual', false],
    ];

    public function run(): void
    {
        $features = Feature::query()->orderBy('id')->get();

        foreach (array_values(self::PLAN) as $index => [$makeName, $modelName, $year, $price, $mileage, $body, $fuel, $transmission, $featured]) {
            $make = Make::where('name', $makeName)->firstOrFail();
            $model = VehicleModel::where('make_id', $make->id)->where('name', $modelName)->firstOrFail();

            $slug = Str::slug("{$makeName} {$modelName} {$year}");

            $vehicle = Vehicle::firstOrCreate(
                ['slug' => $slug],
                [
                    'source' => VehicleSource::Admin,
                    'make_id' => $make->getKey(),
                    'model_id' => $model->getKey(),
                    'body_type_id' => BodyType::where('name', $body)->value('id'),
                    'fuel_type_id' => FuelType::where('name', $fuel)->value('id'),
                    'transmission_id' => Transmission::where('name', $transmission)->value('id'),
                    'exterior_color_id' => Color::orderBy('id')->offset($index % 6)->value('id'),
                    'interior_color_id' => Color::orderBy('id')->offset(($index + 3) % 6)->value('id'),
                    'trim' => ['GX', 'GXi', 'EX', 'Sport', 'Premium', 'Limited'][$index % 6],
                    'year' => $year,
                    'mileage_km' => $mileage,
                    'price' => $price,
                    'condition' => $index % 5 === 0 ? VehicleCondition::CertifiedPreOwned : VehicleCondition::Used,
                    'description' => $this->description($makeName, $modelName, $year, $mileage),
                    'vin' => strtoupper(Str::random(17)),
                    'registration_number' => strtoupper('WP-'.(1000 + $index * 137)),
                    'owners_count' => ($index % 3) + 1,
                    'accident_history' => $index % 4 === 0 ? 'None â€” full service history available' : null,
                    'warranty' => $year >= 2021 ? 'Remaining manufacturer warranty' : null,
                    'last_service_date' => now()->subMonths($index + 1)->toDateString(),
                    'finance_deposit' => round($price * 0.2, 2),
                    'finance_term_months' => [36, 48, 60][$index % 3],
                    'finance_apr' => [9.5, 11.0, 12.5, 14.0][$index % 4],
                    'location' => ['Colombo', 'Gampaha', 'Kandy', 'Galle', 'Negombo', 'Kurunegala'][$index % 6],
                    'status' => VehicleStatus::Published,
                    'published_at' => now()->subDays($index * 3 + 1),
                    'is_featured' => $featured,
                    'views_count' => 120 + ($index * 47) % 900,
                    'meta_title' => "{$year} {$makeName} {$modelName} for sale in Sri Lanka",
                    'meta_description' => "Buy this {$year} {$makeName} {$modelName} â€” {$mileage} km, {$transmission}, {$fuel}. Price LKR ".number_format($price, 0).'.',
                ]
            );

            $this->attachFeatures($vehicle, $features, $index);
            $this->createImages($vehicle, $index);
        }
    }

    private function attachFeatures(Vehicle $vehicle, $features, int $index): void
    {
        if ($vehicle->features()->exists()) {
            return;
        }

        $features->shuffle(random_int(1, 1000))
            ->take(5 + ($index % 4))
            ->each(fn ($feature) => $vehicle->features()->attach($feature->getKey()));
    }

    private function createImages(Vehicle $vehicle, int $index): void
    {
        if ($vehicle->images()->exists()) {
            return;
        }

        $color = $vehicle->exteriorColor?->hex ?: '#4A5568';
        $label = strtoupper($vehicle->make?->name.' '.$vehicle->year);

        foreach ([1, 2, 3] as $position) {
            $slug = $vehicle->slug.'-'.$position;
            $path = "vehicles/{$slug}.jpg";
            $path800 = "vehicles/{$slug}-800w.jpg";
            $path1600 = "vehicles/{$slug}-1600w.jpg";

            $this->storeJpeg($path, 2000, 1250, $color, $label);
            $this->storeJpeg($path1600, 1600, 1000, $color, $label);
            $this->storeJpeg($path800, 800, 500, $color, $label);

            VehicleImage::firstOrCreate(
                [
                    'vehicle_id' => $vehicle->getKey(),
                    'path' => $path,
                ],
                [
                    'path_800w' => $path800,
                    'path_1600w' => $path1600,
                    'alt' => "{$label} â€” photo {$position}",
                    'is_cover' => $position === 1,
                    'sort_order' => $position,
                ]
            );
        }
    }

    private function storeJpeg(string $path, int $width, int $height, string $hex, string $label): void
    {
        $image = imagecreatetruecolor($width, $height);

        [$r, $g, $b] = array_map(
            fn (string $part) => hexdec($part),
            str_split(ltrim($hex, '#'), 2)
        );

        imagefill($image, 0, 0, imagecolorallocate($image, $r, $g, $b));

        $shade = imagecolorallocate($image, max(0, $r - 55), max(0, $g - 55), max(0, $b - 55));
        imagefilledrectangle($image, 0, (int) ($height * 0.72), $width, $height, $shade);

        $text = imagecolorallocate($image, 255, 255, 255);
        $font = 5;
        $textWidth = strlen($label) * imagefontwidth($font);

        imagestring(
            $image,
            $font,
            max(8, (int) (($width - $textWidth) / 2)),
            (int) ($height * 0.8),
            $label,
            $text
        );

        ob_start();
        imagejpeg($image, null, 82);
        $binary = ob_get_clean();

        imagedestroy($image);

        Storage::disk('public')->put($path, $binary);
    }

    private function description(string $make, string $model, int $year, int $mileage): string
    {
        return implode("\n\n", [
            "{$year} {$make} {$model} in excellent condition, carefully maintained with {$mileage} km on the odometer.",
            'Full service history, non-accidental bodywork and a smooth, reliable drive. Suitable for daily commuting and long-distance travel.',
            'Inspection welcome. Trade-in considered. Financing available through our partner banks â€” contact us for a tailored quotation.',
        ]);
    }
}
