<?php

namespace App\Modules\Submissions\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Submissions\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class VehicleSubmission extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'data',
        'images',
        'status',
        'notes',
        'approved_vehicle_id',
        'reviewed_by',
        'reviewed_at',
        'ip',
    ];

    protected $casts = [
        'status' => SubmissionStatus::class,
        'data' => 'array',
        'images' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'approved_vehicle_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Pending);
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            SubmissionStatus::Approved,
            SubmissionStatus::Rejected,
        ]);
    }

    public function reference(): string
    {
        return 'SUB-'.str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }
}
