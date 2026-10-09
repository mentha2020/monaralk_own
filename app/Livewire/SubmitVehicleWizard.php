<?php

namespace App\Livewire;

use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\VehicleModel;
use App\Modules\Shared\Rules\ContactNumber;
use App\Modules\Submissions\Enums\SubmissionStatus;
use App\Modules\Submissions\Models\VehicleSubmission;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class SubmitVehicleWizard extends Component
{
    use WithFileUploads;

    public const MAX_PHOTOS = 10;

    private const DATA_KEYS = [
        'make_id',
        'model_id',
        'year',
        'condition',
        'location',
        'description',
        'mileage_km',
        'price',
        'body_type_id',
        'fuel_type_id',
        'transmission_id',
        'exterior_color_id',
        'trim',
        'registration_number',
        'owners_count',
    ];

    public int $step = 1;

    public array $form = [
        'make_id' => null,
        'model_id' => null,
        'year' => null,
        'condition' => 'used',
        'location' => null,
        'description' => null,
        'mileage_km' => null,
        'price' => null,
        'transmission_id' => null,
        'fuel_type_id' => null,
        'body_type_id' => null,
        'exterior_color_id' => null,
        'trim' => null,
        'registration_number' => null,
        'owners_count' => null,
        'name' => null,
        'email' => null,
        'phone' => null,
    ];

    /** @var array<int, mixed> */
    public array $photos = [];

    public ?int $submissionId = null;

    public ?string $reference = null;

    public function mount(): void
    {
        if ($user = auth()->user()) {
            $this->form['name'] = $user->name;
            $this->form['email'] = $user->email;
        }
    }

    public function updated(string $property): void
    {
        if ($property === 'form.make_id') {
            $this->form['model_id'] = null;
        }
    }

    public function next(): void
    {
        $this->validate($this->rulesFor($this->step));

        $this->step = min($this->step + 1, 5);
    }

    public function back(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);

        $this->photos = array_values($this->photos);
    }

    public function submit(): void
    {
        if ($this->submissionId !== null) {
            return;
        }

        $this->validate($this->rules());

        $submission = VehicleSubmission::create([
            'user_id' => auth()->id(),
            'name' => $this->form['name'],
            'email' => $this->form['email'],
            'phone' => filled($this->form['phone']) ? $this->form['phone'] : null,
            'data' => array_intersect_key($this->form, array_flip(self::DATA_KEYS)),
            'images' => [],
            'status' => SubmissionStatus::Pending,
            'ip' => request()->ip(),
        ]);

        $paths = [];

        foreach ($this->photos as $photo) {
            $paths[] = $photo->store('submissions/'.$submission->getKey(), 'public');
        }

        $submission->update(['images' => $paths]);

        $this->submissionId = $submission->getKey();
        $this->reference = $submission->reference();
        $this->photos = [];
        $this->step = 6;
    }

    public function rulesFor(int $step): array
    {
        return match ($step) {
            1 => [
                'form.make_id' => ['required', 'integer', 'exists:makes,id'],
                'form.model_id' => ['required', 'integer', 'exists:vehicle_models,id'],
                'form.year' => ['required', 'integer', 'between:1950,2030'],
                'form.condition' => ['required', 'in:used,new'],
                'form.location' => ['required', 'string', 'max:120'],
                'form.description' => ['required', 'string', 'max:4000'],
            ],
            2 => [
                'form.mileage_km' => ['required', 'integer', 'min:0', 'max:2000000'],
                'form.price' => ['required', 'numeric', 'min:100000', 'max:1000000000'],
                'form.transmission_id' => ['required', 'integer', 'exists:transmissions,id'],
                'form.fuel_type_id' => ['required', 'integer', 'exists:fuel_types,id'],
                'form.body_type_id' => ['nullable', 'integer', 'exists:body_types,id'],
                'form.exterior_color_id' => ['nullable', 'integer', 'exists:colors,id'],
                'form.trim' => ['nullable', 'string', 'max:60'],
                'form.registration_number' => ['nullable', 'string', 'max:20'],
                'form.owners_count' => ['nullable', 'integer', 'between:1,10'],
            ],
            3 => [
                'photos' => ['required', 'array', 'min:1', 'max:'.self::MAX_PHOTOS],
                'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            4 => [
                'form.name' => ['required', 'string', 'max:255'],
                'form.email' => ['required', 'email', 'max:255'],
                'form.phone' => ['nullable', 'string', 'max:40', new ContactNumber],
            ],
            default => [],
        };
    }

    public function rules(): array
    {
        return array_merge(
            $this->rulesFor(1),
            $this->rulesFor(2),
            $this->rulesFor(3),
            $this->rulesFor(4),
        );
    }

    public function render()
    {
        return view('livewire.submit-vehicle-wizard', [
            'steps' => [
                1 => __('Details'),
                2 => __('Specs'),
                3 => __('Photos'),
                4 => __('Contact'),
                5 => __('Review'),
            ],
            'makes' => Make::query()->where('is_active', true)->orderBy('name')->get(),
            'models' => VehicleModel::query()
                ->when($this->form['make_id'], fn ($query) => $query->where('make_id', $this->form['make_id']))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'bodyTypes' => BodyType::query()->where('is_active', true)->orderBy('name')->get(),
            'fuelTypes' => FuelType::query()->where('is_active', true)->orderBy('name')->get(),
            'transmissions' => Transmission::query()->where('is_active', true)->orderBy('name')->get(),
            'colors' => Color::query()->where('is_active', true)->orderBy('name')->get(),
            'summary' => $this->summary(),
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function summary(): array
    {
        $labels = [
            'make' => __('Make'),
            'model' => __('Model'),
            'year' => __('Year'),
            'condition' => __('Condition'),
            'mileage_km' => __('Mileage (km)'),
            'price' => __('Price'),
            'transmission' => __('Transmission'),
            'fuel_type' => __('Fuel'),
            'body_type' => __('Body'),
            'color' => __('Colour'),
            'trim' => __('Trim'),
            'registration_number' => __('Registration'),
            'owners_count' => __('Previous owners'),
            'location' => __('Location'),
        ];

        $values = [
            'make' => Make::query()->find($this->form['make_id'])?->name,
            'model' => VehicleModel::query()->find($this->form['model_id'])?->name,
            'year' => $this->form['year'],
            'condition' => Str::headline((string) $this->form['condition']),
            'mileage_km' => filled($this->form['mileage_km']) ? number_format((int) $this->form['mileage_km']).' km' : null,
            'price' => filled($this->form['price']) ? 'LKR '.number_format((float) $this->form['price']) : null,
            'transmission' => Transmission::query()->find($this->form['transmission_id'])?->name,
            'fuel_type' => FuelType::query()->find($this->form['fuel_type_id'])?->name,
            'body_type' => BodyType::query()->find($this->form['body_type_id'])?->name,
            'color' => Color::query()->find($this->form['exterior_color_id'])?->name,
            'trim' => $this->form['trim'],
            'registration_number' => $this->form['registration_number'],
            'owners_count' => $this->form['owners_count'],
            'location' => $this->form['location'],
        ];

        $rows = [];

        foreach ($labels as $key => $label) {
            $value = $values[$key];

            if (filled($value)) {
                $rows[] = ['label' => $label, 'value' => (string) $value];
            }
        }

        return $rows;
    }
}
