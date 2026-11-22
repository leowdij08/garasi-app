<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bukti pembayaran untuk sewa baru (rental_extension_id kosong) atau perpanjangan.
 */
class Payment extends Model
{
    protected $fillable = [
        'rental_id', 'rental_extension_id', 'bukti_gambar', 'jumlah', 'diunggah_oleh',
    ];

    protected function casts(): array
    {
        return ['jumlah' => 'decimal:2'];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function extension(): BelongsTo
    {
        return $this->belongsTo(RentalExtension::class, 'rental_extension_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }
}
