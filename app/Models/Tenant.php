<?php

namespace App\Models;

use App\Enums\StatusVerifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    protected $fillable = [
        'user_id', 'nik', 'nama_lengkap', 'nomor_telepon',
        'status_verifikasi', 'diverifikasi_oleh', 'diverifikasi_pada',
    ];

    protected function casts(): array
    {
        return [
            'status_verifikasi' => StatusVerifikasi::class,
            'diverifikasi_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Hanya penyewa APPROVED yang boleh mengajukan sewa (poin 16). */
    public function bolehMenyewa(): bool
    {
        return $this->status_verifikasi === StatusVerifikasi::APPROVED;
    }
}