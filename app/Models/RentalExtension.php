<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Perpanjangan sewa oleh admin, dihitung harian dan tidak dikonversi menjadi bulanan.
 */
class RentalExtension extends Model
{
    protected $fillable = [
        'nomor_perpanjang', 'rental_id', 'admin_id', 'tanggal_selesai_lama',
        'tanggal_selesai_baru', 'tambahan_hari', 'biaya_perpanjangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_selesai_lama' => 'date',
            'tanggal_selesai_baru' => 'date',
            'biaya_perpanjangan' => 'decimal:2',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
