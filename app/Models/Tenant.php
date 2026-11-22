<?php

namespace App\Models;

use App\Enums\StatusVerifikasi;
use App\Exceptions\PendaftaranSudahDiputuskanException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

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

    /** Admin yang memutuskan verifikasi (kosong selama masih PENDING). */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    /** Hanya penyewa APPROVED yang boleh mengajukan sewa (poin 16). */
    public function bolehMenyewa(): bool
    {
        return $this->status_verifikasi === StatusVerifikasi::APPROVED;
    }

    /**
     * Admin menerima (APPROVED) atau menolak (REJECTED) pendaftaran.
     * Hanya berhasil jika status saat ini masih PENDING, sehingga dua keputusan
     * yang bersamaan tidak saling menimpa.
     *
     * @throws PendaftaranSudahDiputuskanException jika sudah bukan PENDING
     */
    public function tentukanVerifikasi(StatusVerifikasi $hasil, User $admin): void
    {
        if ($hasil === StatusVerifikasi::PENDING) {
            throw new InvalidArgumentException('Hasil verifikasi harus APPROVED atau REJECTED.');
        }

        $diubah = static::query()
            ->whereKey($this->getKey())
            ->where('status_verifikasi', StatusVerifikasi::PENDING->value)
            ->update([
                'status_verifikasi' => $hasil->value,
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
            ]);

        if ($diubah === 0) {
            throw new PendaftaranSudahDiputuskanException();
        }

        $this->refresh();
    }
}