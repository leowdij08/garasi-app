<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\RentalExtension;
use Carbon\CarbonInterface;

/** Nomor dokumen: SEW-yyyymmdd-0001 dan PJG-yyyymmdd-0001, urut per hari dengan MAX + 1 (poin 22). */
class DocumentNumberGenerator
{
    public function nomorNota(CarbonInterface $tanggal): string
    {
        return $this->susun('SEW', $tanggal, Rental::class, 'nomor_nota');
    }

    public function nomorPerpanjang(CarbonInterface $tanggal): string
    {
        return $this->susun('PJG', $tanggal, RentalExtension::class, 'nomor_perpanjang');
    }

    private function susun(string $kode, CarbonInterface $tanggal, string $model, string $kolom): string
    {
        $awalan = $kode.'-'.$tanggal->format('Ymd').'-';
        $terakhir = $model::query()->where($kolom, 'like', $awalan.'%')->max($kolom);
        $urut = $terakhir === null ? 1 : ((int) substr($terakhir, -4)) + 1;

        return $awalan.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }
}
