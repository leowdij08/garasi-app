<?php

namespace App\Services;

use App\Enums\TipeSewa;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Menentukan tipe sewa akhir, lama sewa, dan tanggal selesai (poin 1-6 dan 19).
 * Aturan yang sama dijalankan di browser (Alpine.js) dan di server; server tetap menjadi penentu.
 */
class RentalTypeResolver
{
    /** Jumlah hari pada bulan dari tanggal mulai sewa (Februari 2026 = 28, Februari 2028 = 29). */
    public function hariSebulan(CarbonInterface $tanggal): int
    {
        return $tanggal->daysInMonth;
    }

    public function resolve(CarbonInterface $tanggalMulai, TipeSewa $tipe, int $jumlahHari): HasilSewa
    {
        $mulai = CarbonImmutable::instance($tanggalMulai)->startOfDay();
        $hariSebulan = $this->hariSebulan($mulai);
        $notifikasi = null;

        if ($tipe === TipeSewa::HARIAN) {
            if ($jumlahHari < 1) {
                throw new InvalidArgumentException('Jumlah hari sewa harian minimal 1.');
            }

            if ($jumlahHari > $hariSebulan) {
                $tipe = TipeSewa::BULANAN;
                $notifikasi = "Sewa tidak dapat melebihi 1 bulan. Tipe sewa diubah menjadi Bulanan ({$hariSebulan} hari).";
            } elseif ($jumlahHari === $hariSebulan) {
                $tipe = TipeSewa::BULANAN;
                $notifikasi = "Sewa harian {$hariSebulan} hari sama dengan 1 bulan. Tipe sewa diubah menjadi Bulanan ({$hariSebulan} hari).";
            }
        }

        if ($tipe === TipeSewa::BULANAN) {
            $lamaHari = $hariSebulan;
            $jumlahTersimpan = 0;
        } else {
            $lamaHari = $jumlahHari;
            $jumlahTersimpan = $jumlahHari;
        }

        // Tanggal selesai inklusif: hari terakhir pemakaian (poin 6).
        $selesai = $mulai->addDays($lamaHari - 1);

        return new HasilSewa($tipe, $jumlahTersimpan, $lamaHari, $mulai, $selesai, $notifikasi);
    }
}