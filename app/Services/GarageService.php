<?php

namespace App\Services;

use App\Enums\StatusGarasi;
use App\Enums\TipeGarasi;
use App\Exceptions\KuotaGarasiTidakCukupException;
use App\Models\Garage;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bisnis garasi: kuota per tipe dan pembuatan ID berkelompok.
 */
class GarageService
{
    /** Kuota maksimal garasi per tipe (S001-S999, C001-C999, E001-E999). */
    public const MAKS_PER_TIPE = 999;

    /** Nomor urut tertinggi yang sudah dipakai untuk tipe ini; 0 jika belum ada. */
    public function nomorTerakhir(TipeGarasi $tipe): int
    {
        $kode = Garage::where('tipe_garasi', $tipe->value)->max('id');

        return $kode === null ? 0 : (int) substr($kode, 1);
    }

    public function sisaKuota(TipeGarasi $tipe): int
    {
        return self::MAKS_PER_TIPE - $this->nomorTerakhir($tipe);
    }

    /** Contoh: tipe SEPEDA nomor 7 menjadi S007. */
    public function buatKode(TipeGarasi $tipe, int $nomor): string
    {
        return $tipe->prefix().str_pad((string) $nomor, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Membuat garasi sejumlah $jumlah dalam satu transaksi.
     *
     * @return array{0: string, 1: string} kode garasi pertama dan terakhir yang dibuat
     *
     * @throws KuotaGarasiTidakCukupException jika jumlah melebihi sisa kuota tipe
     */
    public function buatBatch(TipeGarasi $tipe, int $jumlah, string|float $hargaHarian, string|float|null $hargaListrik): array
    {
        return DB::transaction(function () use ($tipe, $jumlah, $hargaHarian, $hargaListrik) {
            $terakhir = $this->nomorTerakhir($tipe);
            $sisa = self::MAKS_PER_TIPE - $terakhir;

            if ($jumlah > $sisa) {
                throw new KuotaGarasiTidakCukupException($tipe, $sisa);
            }

            $sekarang = now();
            $baris = [];

            for ($i = 1; $i <= $jumlah; $i++) {
                $baris[] = [
                    'id' => $this->buatKode($tipe, $terakhir + $i),
                    'tipe_garasi' => $tipe->value,
                    'harga_sewa_harian' => $hargaHarian,
                    // Harga listrik hanya berlaku untuk garasi mobil listrik.
                    'harga_listrik_kwh' => $tipe === TipeGarasi::LISTRIK ? $hargaListrik : null,
                    'status' => StatusGarasi::AVAILABLE->value,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }

            Garage::insert($baris);

            return [$baris[0]['id'], $baris[$jumlah - 1]['id']];
        });
    }
}
