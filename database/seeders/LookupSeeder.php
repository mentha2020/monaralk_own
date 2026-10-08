<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Feature;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\VehicleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMakesAndModels();
        $this->seedSimple(BodyType::class, [
            'Sedan', 'SUV', 'Hatchback', 'Coupe', 'Wagon', 'Pickup', 'Van', 'Crossover', 'Minivan',
        ]);
        $this->seedSimple(FuelType::class, ['Petrol', 'Diesel', 'Hybrid', 'Electric']);
        $this->seedSimple(Transmission::class, ['Manual', 'Automatic', 'CVT']);
        $this->seedColors();
        $this->seedFeatures();
    }

    private function seedMakesAndModels(): void
    {
        $catalog = [
            'Toyota' => ['country' => 'Japan', 'models' => ['Corolla', 'Aqua', 'Vitz', 'Prius', 'Land Cruiser', 'Hilux']],
            'Honda' => ['country' => 'Japan', 'models' => ['Civic', 'Fit', 'Vezel', 'CR-V', 'HR-V']],
            'Suzuki' => ['country' => 'Japan', 'models' => ['Alto', 'Wagon R', 'Swift', 'Vitara', 'Every']],
            'Nissan' => ['country' => 'Japan', 'models' => ['Sunny', 'Leaf', 'X-Trail', 'March']],
            'Mitsubishi' => ['country' => 'Japan', 'models' => ['Lancer', 'Outlander', 'Montero Sport', 'Canter']],
            'Mazda' => ['country' => 'Japan', 'models' => ['Axela', 'Demio', 'CX-5', 'BT-50']],
            'Subaru' => ['country' => 'Japan', 'models' => ['Impreza', 'Forester', 'Outback']],
            'Kia' => ['country' => 'South Korea', 'models' => ['Picanto', 'Cerato', 'Seltos', 'Sportage']],
            'Hyundai' => ['country' => 'South Korea', 'models' => ['i10', 'Elantra', 'Tucson', 'Creta']],
            'Perodua' => ['country' => 'Malaysia', 'models' => ['Axia', 'Bezza', 'Ayla']],
            'BMW' => ['country' => 'Germany', 'models' => ['3 Series', '5 Series', 'X1', 'X5']],
            'Mercedes-Benz' => ['country' => 'Germany', 'models' => ['C-Class', 'E-Class', 'GLC']],
            'Volkswagen' => ['country' => 'Germany', 'models' => ['Golf', 'Polo', 'Tiguan']],
            'Daihatsu' => ['country' => 'Japan', 'models' => ['Mira', 'Hijet', 'Terios']],
        ];

        $position = 0;

        foreach ($catalog as $makeName => $config) {
            $position++;

            $make = Make::firstOrCreate(
                ['slug' => Str::slug($makeName)],
                [
                    'name' => $makeName,
                    'country' => $config['country'],
                    'is_active' => true,
                    'sort_order' => $position,
                ]
            );

            foreach (array_values($config['models']) as $position => $modelName) {
                VehicleModel::firstOrCreate(
                    ['make_id' => $make->id, 'slug' => Str::slug($modelName)],
                    [
                        'name' => $modelName,
                        'is_active' => true,
                        'sort_order' => $position + 1,
                    ]
                );
            }
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $names
     */
    private function seedSimple(string $model, array $names): void
    {
        foreach (array_values($names) as $index => $name) {
            $model::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function seedColors(): void
    {
        $colors = [
            'White' => '#FFFFFF',
            'Black' => '#111111',
            'Silver' => '#C0C0C0',
            'Grey' => '#808080',
            'Red' => '#C0392B',
            'Blue' => '#2471A3',
            'Pearl White' => '#F5F5F5',
            'Navy' => '#1B2631',
            'Beige' => '#E8DCC0',
            'Bronze' => '#CD7F32',
        ];

        $position = 0;

        foreach ($colors as $name => $hex) {
            $position++;

            Color::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'hex' => $hex,
                    'is_active' => true,
                    'sort_order' => $position,
                ]
            );
        }
    }

    private function seedFeatures(): void
    {
        $features = [
            'Safety' => ['ABS', 'Front Airbags', 'Rear Camera', 'Parking Sensors', 'Hill Start Assist', 'Stability Control'],
            'Comfort' => ['Air Conditioning', 'Sunroof', 'Leather Seats', 'Cruise Control', 'Keyless Entry', 'Push Button Start'],
            'Entertainment' => ['Bluetooth', 'Navigation System', 'Apple CarPlay', 'Android Auto', 'Premium Sound System'],
            'Exterior' => ['Alloy Wheels', 'Roof Rails', 'LED Headlights', 'Fog Lamps', 'Power Mirrors'],
        ];

        $position = 0;

        foreach ($features as $group => $names) {
            foreach ($names as $name) {
                $position++;

                Feature::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'name' => $name,
                        'group_name' => $group,
                        'is_active' => true,
                        'sort_order' => $position,
                    ]
                );
            }
        }
    }
}
