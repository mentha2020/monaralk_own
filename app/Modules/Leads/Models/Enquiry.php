<?php

namespace App\Modules\Leads\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Leads\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'name',
        'email',
        'phone',
        'message',
        'status',
        'notes',
        'ip',
    ];

    protected $casts = [
        'status' => EnquiryStatus::class,
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [EnquiryStatus::New, EnquiryStatus::Contacted]);
    }

    public function scopeForVehicle(Builder $query, Vehicle|int $vehicle): Builder
    {
        return $query->where('vehicle_id', $vehicle instanceof Vehicle ? $vehicle->getKey() : $vehicle);
    }
}
