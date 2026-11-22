<?php

namespace App\Services;

use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use App\Enums\TipeSewa;
use InvalidArgumentException;

/** Perhitungan biaya sewa, listrik, dan perpanjangan (poin 2, 10, 17). */
class PricingService
{
    /** Harga sewa bulanan = 25 x harga sewa harian. */
    public const FAKTOR_BULANAN = 25;

    public function hargaBulanan(float|int|string $hargaHarian): float
    {
        return round(self::FAKTOR_BULANAN * (float) $hargaHarian, 2);
    }

    /** Bulanan: tepat 25 x harga harian berapa pun jumlah hari bulannya. Harian: hari x harga harian. */
    public function biayaSewa(TipeSewa $tipe, int $lamaHari, float|int|string $hargaHarian): float
    {
        if ($tipe === TipeSewa::BULANAN) {
            return $this->hargaBulanan($hargaHarian);
        }

        return round($lamaHari * (float) $hargaHarian, 2);
    }

    /**
     * Biaya isi listrik = (kapasitas baterai - sisa baterai) x harga per kWh.
     *
     * @throws InvalidArgumentException jika sisa di luar rentang 0..kapasitas
     */
    public function biayaListrik(float|int|string $kapasitas, float|int|string $sisa, float|int|string $hargaKwh): float
    {
        $kapasitas = (float) $kapasitas;
        $sisa = (float) $sisa;

        if ($sisa < 0 || $sisa > $kapasitas) {
            throw new InvalidArgumentException('Sisa baterai harus di antara 0 dan kapasitas baterai.');
        }

        return round(($kapasitas - $sisa) * (float) $hargaKwh, 2);
    }

    /** Biaya listrik hanya untuk mobil listrik yang diparkir di garasi mobil listrik. */
    public function berlakuBiayaListrik(TipeKendaraan $kendaraan, TipeGarasi $garasi): bool
    {
        return $kendaraan === TipeKendaraan::MOBIL_LISTRIK && $garasi === TipeGarasi::LISTRIK;
    }

    /** Perpanjangan dihitung harian dan tidak dikonversi ke bulanan (poin 17). */
    public function biayaPerpanjangan(float|int|string $hargaHarian, int $tambahanHari): float
    {
        if ($tambahanHari < 1) {
            throw new InvalidArgumentException('Tambahan hari perpanjangan minimal 1.');
        }

        return round($tambahanHari * (float) $hargaHarian, 2);
    }
}
