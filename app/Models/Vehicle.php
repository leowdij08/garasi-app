<?php

namespace App\Models;

use App\Enums\TipeKendaraan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'tenant_id', 'tipe_kendaraan', 'nomor_plat', 'merek', 'model', 'kapasitas_baterai_kwh',
    ];

    protected function casts(): array
    {
        return [
            'tipe_kendaraan' => TipeKendaraan::class,
            'kapasitas_baterai_kwh' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}