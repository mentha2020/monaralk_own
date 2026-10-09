<?php

namespace App\Modules\Accounts\Services;

use App\Modules\Accounts\Enums\SavedType;
use App\Modules\Accounts\Models\SavedVehicle;
use App\Modules\Catalog\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class SavedVehicles
{
    public const COMPARE_CAP = 4;

    public const FAVOURITE_CAP = 100;

    /**
     * @return array{favourite: int, compare: int}
     */
    public function counts(int $userId): array
    {
        $counts = ['favourite' => 0, 'compare' => 0];

        SavedVehicle::query()
            ->where('user_id', $userId)
            ->pluck('type')
            ->each(function (mixed $type) use (&$counts): void {
                if ($type instanceof SavedType) {
                    $counts[$type->value]++;
                }
            });

        return $counts;
    }

    /**
     * @return array{favourite: bool, compare: bool}
     */
    public function state(int $userId, int $vehicleId): array
    {
        $types = SavedVehicle::query()
            ->where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->pluck('type');

        return [
            'favourite' => $types->contains(fn (mixed $type): bool => $type === SavedType::Favourite),
            'compare' => $types->contains(fn (mixed $type): bool => $type === SavedType::Compare),
        ];
    }

    /**
     * @return array{saved: bool, message: string|null, counts: array{favourite: int, compare: int}}
     */
    public function toggle(int $userId, int $vehicleId, SavedType $type): array
    {
        return DB::transaction(function () use ($userId, $vehicleId, $type): array {
            $existing = SavedVehicle::query()
                ->where('user_id', $userId)
                ->where('vehicle_id', $vehicleId)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof SavedVehicle) {
                $existing->delete();

                return ['saved' => false, 'message' => null, 'counts' => $this->counts($userId)];
            }

            $cap = self::cap($type);
            $current = SavedVehicle::query()->where('user_id', $userId)->where('type', $type)->count();

            if ($current >= $cap) {
                return [
                    'saved' => false,
                    'message' => self::capMessage($type),
                    'counts' => $this->counts($userId),
                ];
            }

            SavedVehicle::create([
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'type' => $type,
            ]);

            return ['saved' => true, 'message' => null, 'counts' => $this->counts($userId)];
        });
    }

    /**
     * Merges browser storage into the account, de-duplicating and honouring the caps.
     *
     * @param  array<int, int|string>  $favourites
     * @param  array<int, int|string>  $compares
     */
    public function merge(int $userId, array $favourites, array $compares): void
    {
        DB::transaction(function () use ($userId, $favourites, $compares): void {
            $this->attach($userId, $favourites, SavedType::Favourite, self::FAVOURITE_CAP);
            $this->attach($userId, $compares, SavedType::Compare, self::COMPARE_CAP);
        });
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    private function attach(int $userId, array $ids, SavedType $type, int $cap): void
    {
        $wanted = collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($wanted->isEmpty()) {
            return;
        }

        $existing = SavedVehicle::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->pluck('vehicle_id')
            ->map(fn (mixed $id): int => (int) $id);

        $available = Vehicle::query()
            ->whereIn('id', $wanted)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id);

        foreach ($available as $vehicleId) {
            if ($existing->contains($vehicleId)) {
                continue;
            }

            if ($existing->count() >= $cap) {
                break;
            }

            SavedVehicle::create([
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'type' => $type,
            ]);

            $existing->push($vehicleId);
        }
    }

    public static function cap(SavedType $type): int
    {
        return $type === SavedType::Compare ? self::COMPARE_CAP : self::FAVOURITE_CAP;
    }

    public static function capMessage(SavedType $type): string
    {
        return match ($type) {
            SavedType::Compare => __('Compare is limited to :count cars.', ['count' => self::COMPARE_CAP]),
            SavedType::Favourite => __('You have reached the maximum of :count favourites.', ['count' => self::FAVOURITE_CAP]),
        };
    }
}
