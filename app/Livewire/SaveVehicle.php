<?php

namespace App\Livewire;

use App\Modules\Accounts\Enums\SavedType;
use App\Modules\Accounts\Services\SavedVehicles;
use Livewire\Component;

class SaveVehicle extends Component
{
    public int $vehicleId = 0;

    public bool $favourite = false;

    public bool $compare = false;

    public ?string $notice = null;

    public function mount(int $vehicleId): void
    {
        abort_unless(auth()->check(), 403);

        $this->vehicleId = $vehicleId;

        $state = app(SavedVehicles::class)->state((int) auth()->id(), $vehicleId);

        $this->favourite = $state['favourite'];
        $this->compare = $state['compare'];
    }

    public function toggleFavourite(): void
    {
        $this->toggle(SavedType::Favourite);
    }

    public function toggleCompare(): void
    {
        $this->toggle(SavedType::Compare);
    }

    private function toggle(SavedType $type): void
    {
        $result = app(SavedVehicles::class)->toggle((int) auth()->id(), $this->vehicleId, $type);

        if ($type === SavedType::Favourite) {
            $this->favourite = $result['saved'];
        } else {
            $this->compare = $result['saved'];
        }

        $this->notice = $result['message'];

        $this->dispatch('saved-changed', ...$result['counts']);
    }

    public function render()
    {
        return view('livewire.save-vehicle');
    }
}
