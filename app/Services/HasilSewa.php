<?php

namespace App\Services;

use App\Enums\TipeSewa;
use Carbon\CarbonImmutable;

/** Hasil penentuan tipe sewa: nilai akhir setelah konversi otomatis. */
final class HasilSewa
{
    public function __construct(
        public readonly TipeSewa $tipeSewa,
        /** Nilai yang disimpan di kolom jumlah_hari: sewa harian = hari yang diinput, bulanan = 0 (poin 21). */
        public readonly int $jumlahHari,
        /** Lama sewa sebenarnya dalam hari. */
        public readonly int $lamaHari,
        public readonly CarbonImmutable $tanggalMulai,
        public readonly CarbonImmutable $tanggalSelesai,
        /** Pesan untuk penyewa jika tipe sewa berubah otomatis; null jika tidak berubah. */
        public readonly ?string $notifikasi,
    ) {
    }

    public function diubahOtomatis(): bool
    {
        return $this->notifikasi !== null;
    }
}