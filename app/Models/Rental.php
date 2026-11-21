<?php

namespace App\Models;

use App\Enums\StatusSewa;
use App\Enums\TipeSewa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    protected $fillable = [
        'nomor_nota', 'tenant_id', 'garage_id', 'vehicle_id', 'tipe_sewa', 'jumlah_hari',
        'tanggal_mulai', 'tanggal_selesai', 'harga_sewa_harian', 'biaya_sewa',
        'harga_listrik_kwh', 'sisa_baterai_kwh', 'biaya_listrik', 'total_biaya', 'status',
    ];

    protected function casts(): array
    {
        return [
            'tipe_sewa' => TipeSewa::class,
            'status' => StatusSewa::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'harga_sewa_harian' => 'decimal:2',
            'biaya_sewa' => 'decimal:2',
            'harga_listrik_kwh' => 'decimal:2',
            'sisa_baterai_kwh' => 'decimal:2',
            'biaya_listrik' => 'decimal:2',
            'total_biaya' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}