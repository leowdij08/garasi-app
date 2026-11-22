<?php

namespace App\Http\Controllers\Penyewa;

use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use App\Exceptions\AturanSewaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Rental;
use App\Services\GarageAvailabilityService;
use App\Services\PricingService;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Penyewa: riwayat sewa dan pengajuan sewa baru.
 */
class RentalController extends Controller
{
    public function __construct(
        private readonly RentalService $rentals,
        private readonly GarageAvailabilityService $ketersediaan,
    ) {}

    /** Riwayat sewa milik penyewa yang sedang login. */
    public function index(Request $request): View
    {
        $penyewa = $request->user()->tenant;
        abort_if($penyewa === null, 403);

        $daftar = Rental::with(['garage', 'vehicle', 'payments'])
            ->where('tenant_id', $penyewa->id)
            ->latest('id')
            ->paginate(15);

        return view('penyewa.rentals.index', compact('daftar'));
    }

    /** Form Sewa Baru. Hanya untuk penyewa yang sudah diverifikasi (poin 16). */
    public function create(Request $request): View|RedirectResponse
    {
        $penyewa = $request->user()->tenant;
        abort_if($penyewa === null, 403);

        if (! $penyewa->bolehMenyewa()) {
            return redirect()->route('penyewa.dashboard')
                ->with('error', 'Akun Anda belum diverifikasi admin. Anda belum dapat menyewa garasi.');
        }

        $hariIni = CarbonImmutable::today();

        $garasi = [];
        foreach (TipeGarasi::cases() as $tipe) {
            $garasi[$tipe->value] = $this->ketersediaan->tersedia($tipe, $hariIni)
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'harian' => (float) $g->harga_sewa_harian,
                    'listrik' => $g->harga_listrik_kwh !== null ? (float) $g->harga_listrik_kwh : null,
                ])->values()->all();
        }

        $konfigurasi = [
            'today' => $hariIni->toDateString(),
            'faktor' => PricingService::FAKTOR_BULANAN,
            'tipeKendaraan' => collect(TipeKendaraan::cases())->map(fn (TipeKendaraan $t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'wajibPlat' => $t->wajibPlat(),
                'garasi' => array_map(fn (TipeGarasi $g) => $g->value, $t->tipeGarasiDiizinkan()),
            ])->values()->all(),
            'tipeGarasi' => collect(TipeGarasi::cases())->map(fn (TipeGarasi $t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ])->values()->all(),
            'garasi' => $garasi,
            'old' => old(),
        ];

        return view('penyewa.rentals.create', compact('konfigurasi'));
    }

    public function store(StoreRentalRequest $request): RedirectResponse
    {
        $penyewa = $request->user()->tenant;

        try {
            [$sewa, $hasil] = $this->rentals->buat($penyewa, $request->validated(), $request->file('bukti_bayar'));
        } catch (AturanSewaException $e) {
            return back()->withInput()->withErrors(['sewa' => $e->getMessage()]);
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors([
                'sewa' => 'Terjadi bentrokan data karena ada permintaan bersamaan. Silakan kirim ulang.',
            ]);
        }

        $pesan = "Sewa berhasil disimpan. Nomor nota {$sewa->nomor_nota}: garasi {$sewa->garage_id}, "
            .$sewa->tanggal_mulai->format('d/m/Y').' s.d. '.$sewa->tanggal_selesai->format('d/m/Y')
            .', total Rp '.number_format((float) $sewa->total_biaya, 0, ',', '.').'.';

        return redirect()->route('penyewa.rentals.index')
            ->with('status', $pesan)
            ->with('info', $hasil->notifikasi);
    }
}
