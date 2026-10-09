<?php

namespace App\Modules\Catalog\Observers;

use App\Jobs\GenerateVehicleImageVariants;
use App\Modules\Catalog\Models\VehicleImage;
use App\Modules\Catalog\Services\VehicleImageService;

class VehicleImageObserver
{
    public function __construct(private readonly VehicleImageService $images) {}

    public function created(VehicleImage $image): void
    {
        GenerateVehicleImageVariants::dispatch($image);
        $this->images->enforceSingleCover($image);
    }

    public function updated(VehicleImage $image): void
    {
        if ($image->wasChanged('path')) {
            $this->images->deleteFiles(
                $image->getOriginal('path'),
                $image->getOriginal('path_800w'),
                $image->getOriginal('path_1600w'),
                $image->getOriginal('path_og'),
            );

            GenerateVehicleImageVariants::dispatch($image);
        }

        if ($image->wasChanged('is_cover')) {
            $this->images->enforceSingleCover($image);
        }
    }

    public function deleting(VehicleImage $image): void
    {
        $this->images->purge($image);
    }
}
