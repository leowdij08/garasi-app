<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusSewa;
use App\Enums\StatusVerifikasi;
use App\Enums\TipeGarasi;
use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\Tenant;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

/**
 * Admin: ringkasan kondisi garasi, pendaftar, sewa aktif, dan pendapatan bulan ini.
 */
class DashboardController extends Controller
{
    public function __invoke(ReportService $laporan): View
    {
        $hariIni = CarbonImmutable::today();
        $tanggal = $hariIni->toDateString();

        $sewaHariIni = fn ($q) => $q
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal);

        $kartuGarasi = collect(TipeGarasi::cases())->map(function (TipeGarasi $tipe) use ($sewaHariIni) {
            $total = Garage::where('tipe_garasi', $tipe->value)->count();
            $terpakai = Garage::where('tipe_garasi', $tipe->value)->whereHas('rentals', $sewaHariIni)->count();

            return ['tipe' => $tipe, 'total' => $total, 'terpakai' => $terpakai, 'tersedia' => $total - $terpakai];
        });

        $menunggu = Tenant::where('status_verifikasi', StatusVerifikasi::PENDING->value)->count();

        $sewaAktif = Rental::where('status', StatusSewa::AKTIF->value)
            ->where($sewaHariIni)
            ->count();

        $pendapatanBulanIni = $laporan->perBulan($hariIni->year)['baris'][$hariIni->month - 1]['pendapatan'];
        $namaBulan = ReportService::BULAN[$hariIni->month].' '.$hariIni->year;

        return view('admin.dashboard', compact('kartuGarasi', 'menunggu', 'sewaAktif', 'pendapatanBulanIni', 'namaBulan'));
    }
}
