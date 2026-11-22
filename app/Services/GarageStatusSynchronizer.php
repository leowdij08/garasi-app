<?php

namespace App\Services;

use App\Enums\StatusGarasi;
use App\Enums\StatusSewa;
use App\Models\Garage;
use App\Models\Rental;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan kolom status dengan tanggal sewa (poin 15 dan 25).
 * Hari terakhir bersifat inklusif, jadi sewa dianggap selesai keesokan harinya.
 */
class GarageStatusSynchronizer
{
    /**
     * @return array{0: int, 1: int} jumlah sewa yang diselesaikan dan jumlah garasi yang dikembalikan ke AVAILABLE
     */
    public function sinkron(?CarbonInterface $hariIni = null): array
    {
        $tgl = ($hariIni ?? now())->toDateString();

        return DB::transaction(function () use ($tgl) {
            $kedaluwarsa = Rental::query()
                ->where('status', StatusSewa::AKTIF->value)
                ->where('tanggal_selesai', '<', $tgl);

            $garasiIds = (clone $kedaluwarsa)->pluck('garage_id')->unique()->values();

            if ($garasiIds->isEmpty()) {
                return [0, 0];
            }

            $jumlahSewa = $kedaluwarsa->update(['status' => StatusSewa::SELESAI->value]);

            // Garasi yang masih punya sewa aktif lain tetap USED.
            $masihAktif = Rental::query()
                ->where('status', StatusSewa::AKTIF->value)
                ->whereIn('garage_id', $garasiIds)
                ->pluck('garage_id')
                ->unique();

            $dikembalikan = Garage::query()
                ->whereIn('id', $garasiIds->diff($masihAktif)->values())
                ->update(['status' => StatusGarasi::AVAILABLE->value]);

            return [$jumlahSewa, $dikembalikan];
        });
    }
}