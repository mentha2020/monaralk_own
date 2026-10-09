<?php

namespace App\Jobs;

use App\Modules\Catalog\Models\VehicleImage;
use App\Modules\Catalog\Services\VehicleImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateVehicleImageVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public VehicleImage $image) {}

    public function handle(VehicleImageService $images): void
    {
        $images->generateVariants($this->image);
    }
}
