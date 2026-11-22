<?php

namespace App\Services;

use App\Enums\TipeKendaraan;
use App\Exceptions\DataSewaTidakValidException;
use App\Exceptions\NomorPlatDipakaiPenyewaLainException;
use App\Models\Tenant;
use App\Models\Vehicle;

/** Mencari atau membuat kendaraan saat sewa baru (poin 12 dan 24). */
class VehicleResolver
{
    /** Huruf besar, tanpa spasi. Kosong dianggap tidak ada plat. */
    public function normalisasiPlat(?string $plat): ?string
    {
        if ($plat === null) {
            return null;
        }

        $bersih = strtoupper((string) preg_replace('/\s+/', '', $plat));

        return $bersih === '' ? null : $bersih;
    }

    /**
     * Plat sudah terdaftar atas penyewa yang sama: dipakai ulang dan datanya diperbarui.
     * Terdaftar atas penyewa lain: ditolak. Tanpa plat (sepeda): selalu dibuat baru.
     * Harus dipanggil di dalam transaksi karena baris kendaraan dikunci.
     */
    public function resolve(Tenant $penyewa, TipeKendaraan $tipe, ?string $plat, string $merek, string $model, ?float $kapasitas): Vehicle
    {
        $plat = $tipe->wajibPlat() ? $this->normalisasiPlat($plat) : null;

        if ($tipe->wajibPlat() && $plat === null) {
            throw new DataSewaTidakValidException('Nomor plat wajib diisi untuk '.$tipe->label().'.');
        }

        if ($tipe === TipeKendaraan::MOBIL_LISTRIK) {
            if ($kapasitas === null || $kapasitas <= 0) {
                throw new DataSewaTidakValidException('Kapasitas baterai wajib diisi untuk mobil listrik.');
            }
        } else {
            $kapasitas = null;
        }

        $data = [
            'tipe_kendaraan' => $tipe,
            'merek' => $merek,
            'model' => $model,
            'kapasitas_baterai_kwh' => $kapasitas,
        ];

        if ($plat !== null) {
            $ada = Vehicle::query()->where('nomor_plat', $plat)->lockForUpdate()->first();

            if ($ada !== null) {
                if ((int) $ada->tenant_id !== (int) $penyewa->id) {
                    throw new NomorPlatDipakaiPenyewaLainException($plat);
                }

                $ada->update($data);

                return $ada;
            }
        }

        return Vehicle::create($data + ['tenant_id' => $penyewa->id, 'nomor_plat' => $plat]);
    }
}