<?php

namespace App\Services;

use App\Enums\TipeGarasi;
use App\Models\Garage;
use App\Models\Rental;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Ketersediaan selalu dihitung dari irisan tanggal sewa; kolom status garasi hanya cache (poin 15).
 */
class GarageAvailabilityService
{
    /** Garasi bertipe tertentu yang tidak punya sewa pada tanggal tersebut. */
    public function tersedia(TipeGarasi $tipe, CarbonInterface $tanggal): Collection
    {
        $tgl = $tanggal->toDateString();

        return Garage::query()
            ->where('tipe_garasi', $tipe->value)
            ->whereDoesntHave('rentals', fn ($q) => $q
                ->where('tanggal_mulai', '<=', $tgl)
                ->where('tanggal_selesai', '>=', $tgl))
            ->orderBy('id')
            ->get();
    }

    /** Dua rentang bertabrakan jika mulai1 <= selesai2 dan selesai1 >= mulai2 (hari yang sama ikut dihitung). */
    public function garasiBentrok(string $garageId, CarbonInterface $mulai, CarbonInterface $selesai): bool
    {
        return Rental::query()
            ->where('garage_id', $garageId)
            ->where('tanggal_mulai', '<=', $selesai->toDateString())
            ->where('tanggal_selesai', '>=', $mulai->toDateString())
            ->exists();
    }

    public function kendaraanBentrok(int $vehicleId, CarbonInterface $mulai, CarbonInterface $selesai): bool
    {
        return Rental::query()
            ->where('vehicle_id', $vehicleId)
            ->where('tanggal_mulai', '<=', $selesai->toDateString())
            ->where('tanggal_selesai', '>=', $mulai->toDateString())
            ->exists();
    }
}