<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleImageService
{
    public const DISK = 'public';

    public const DIRECTORY = 'vehicles';

    public const ORIGINAL_WIDTH = 2000;

    public const VARIANTS = [
        '-800w' => 800,
        '-1600w' => 1600,
    ];

    public const OG_WIDTH = 1200;

    public const OG_HEIGHT = 630;

    public function store(Vehicle $vehicle, UploadedFile $file, ?string $alt = null, bool $isCover = false, ?int $sortOrder = null): VehicleImage
    {
        return DB::transaction(function () use ($vehicle, $file, $alt, $isCover, $sortOrder) {
            $name = $vehicle->slug.'-'.Str::lower(Str::random(6));
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $path = $file->storeAs(self::DIRECTORY, $name.'.'.$extension, self::DISK);

            $this->optimizeOriginal($path);

            return $vehicle->images()->create([
                'path' => $path,
                'alt' => $alt,
                'is_cover' => $isCover,
                'sort_order' => $sortOrder ?? ((int) $vehicle->images()->max('sort_order') + 1),
            ]);
        });
    }

    public function optimizeOriginal(string $path): void
    {
        $storage = Storage::disk(self::DISK);

        if (! $storage->exists($path)) {
            return;
        }

        $source = $storage->path($path);
        $info = @getimagesize($source);

        if ($info === false) {
            return;
        }

        [$width, $height] = $info;

        if ($width <= self::ORIGINAL_WIDTH) {
            return;
        }

        $gd = $this->decode($source, $info['mime']);

        if ($gd === null) {
            return;
        }

        $renderWidth = self::ORIGINAL_WIDTH;
        $renderHeight = max(1, (int) round($height * ($renderWidth / $width)));

        $canvas = imagecreatetruecolor($renderWidth, $renderHeight);
        imagecopyresampled($canvas, $gd, 0, 0, 0, 0, $renderWidth, $renderHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 85);
        $binary = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($gd);

        $storage->put($path, $binary);
    }

    public function generateVariants(VehicleImage $image): void
    {
        $storage = Storage::disk(self::DISK);

        if (! $storage->exists($image->path)) {
            return;
        }

        $source = $storage->path($image->path);
        $info = @getimagesize($source);

        if ($info === false) {
            return;
        }

        [$width, $height] = $info;
        $gd = $this->decode($source, $info['mime']);

        if ($gd === null || $width < 1 || $height < 1) {
            return;
        }

        $updated = [];

        foreach (self::VARIANTS as $suffix => $targetWidth) {
            $variantPath = $this->variantPath($image->path, $suffix);
            $renderWidth = max(1, min($targetWidth, $width));
            $renderHeight = max(1, (int) round($height * ($renderWidth / $width)));

            $canvas = imagecreatetruecolor($renderWidth, $renderHeight);
            imagecopyresampled($canvas, $gd, 0, 0, 0, 0, $renderWidth, $renderHeight, $width, $height);

            ob_start();
            imagejpeg($canvas, null, 82);
            $storage->put($variantPath, ob_get_clean());

            $webpPath = $this->variantPath($image->path, $suffix, 'webp');

            $updated[$suffix === '-800w' ? 'path_800w' : 'path_1600w'] = $variantPath;
            $updated[$suffix === '-800w' ? 'path_800w_webp' : 'path_1600w_webp'] = $this->generateWebp($canvas, $webpPath) ? $webpPath : null;

            imagedestroy($canvas);
        }

        $ogPath = $this->variantPath($image->path, '-og');

        if ($this->generateOgImage($gd, $width, $height, $ogPath)) {
            $updated['path_og'] = $ogPath;
        }

        imagedestroy($gd);

        $image->forceFill($updated)->saveQuietly();
    }

    public function purge(VehicleImage $image): void
    {
        DB::transaction(function () use ($image) {
            $this->deleteFiles(
                $image->getOriginal('path'),
                $image->getOriginal('path_800w'),
                $image->getOriginal('path_1600w'),
                $image->getOriginal('path_og'),
                $image->getOriginal('path_800w_webp'),
                $image->getOriginal('path_1600w_webp'),
            );
        });
    }

    public function deleteFiles(?string ...$paths): void
    {
        $storage = Storage::disk(self::DISK);

        foreach (array_filter($paths) as $path) {
            if ($storage->exists($path)) {
                $storage->delete($path);
            }
        }
    }

    public function enforceSingleCover(VehicleImage $image): void
    {
        if (! $image->is_cover || $image->vehicle_id === null) {
            return;
        }

        VehicleImage::query()
            ->where('vehicle_id', $image->vehicle_id)
            ->whereKeyNot($image->getKey())
            ->update(['is_cover' => false]);
    }

    public function variantPath(string $path, string $suffix, string $extension = 'jpg'): string
    {
        $directory = str_replace('\\', '/', dirname($path));
        $basename = pathinfo($path, PATHINFO_FILENAME);

        return ($directory === '.' ? '' : $directory.'/').$basename.$suffix.'.'.$extension;
    }

    private function generateWebp($canvas, string $webpPath): bool
    {
        if (! function_exists('imagewebp')) {
            return false;
        }

        ob_start();
        imagewebp($canvas, null, 80);
        $binary = ob_get_clean();

        if ($binary === '') {
            return false;
        }

        Storage::disk(self::DISK)->put($webpPath, $binary);

        return true;
    }

    private function generateOgImage($gd, int $width, int $height, string $ogPath): bool
    {
        if ($width < 1 || $height < 1) {
            return false;
        }

        $scale = max(self::OG_WIDTH / $width, self::OG_HEIGHT / $height);
        $cropWidth = max(1, (int) round(self::OG_WIDTH / $scale));
        $cropHeight = max(1, (int) round(self::OG_HEIGHT / $scale));
        $cropX = max(0, (int) (($width - $cropWidth) / 2));
        $cropY = max(0, (int) (($height - $cropHeight) / 2));
        $cropWidth = max(1, min($cropWidth, $width - $cropX));
        $cropHeight = max(1, min($cropHeight, $height - $cropY));

        $canvas = imagecreatetruecolor(self::OG_WIDTH, self::OG_HEIGHT);
        imagecopyresampled($canvas, $gd, 0, 0, $cropX, $cropY, self::OG_WIDTH, self::OG_HEIGHT, $cropWidth, $cropHeight);

        ob_start();
        imagejpeg($canvas, null, 85);
        $binary = ob_get_clean();

        imagedestroy($canvas);

        if ($binary === '') {
            return false;
        }

        Storage::disk(self::DISK)->put($ogPath, $binary);

        return true;
    }

    /**
     * @return resource|null
     */
    private function decode(string $absolutePath, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            'image/gif' => @imagecreatefromgif($absolutePath),
            default => null,
        };
    }
}
