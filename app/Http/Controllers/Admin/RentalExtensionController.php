<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusSewa;
use App\Exceptions\AturanSewaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExtensionRequest;
use App\Models\Rental;
use App\Services\RentalExtensionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin: perpanjangan sewa yang sedang aktif.
 */
class RentalExtensionController extends Controller
{
    public function __construct(private readonly RentalExtensionService $layanan) {}

    /** Daftar garasi yang sedang disewa pada hari ini. */
    public function index(): View
    {
        $hariIni = CarbonImmutable::today()->toDateString();

        $daftar = Rental::with(['garage', 'vehicle', 'tenant', 'extensions'])
            ->where('status', StatusSewa::AKTIF->value)
            ->where('tanggal_mulai', '<=', $hariIni)
            ->where('tanggal_selesai', '>=', $hariIni)
            ->orderBy('garage_id')
            ->paginate(20);

        return view('admin.extensions.index', compact('daftar'));
    }

    public function create(Rental $rental): View|RedirectResponse
    {
        if ($rental->status !== StatusSewa::AKTIF || ! $rental->aktifPada(CarbonImmutable::today())) {
            return redirect()->route('admin.extensions.index')
                ->withErrors(['perpanjang' => 'Hanya sewa yang aktif pada hari ini yang dapat diperpanjang.']);
        }

        $rental->load(['garage', 'vehicle', 'tenant', 'extensions']);

        return view('admin.extensions.create', compact('rental'));
    }

    public function store(StoreExtensionRequest $request, Rental $rental): RedirectResponse
    {
        try {
            $p = $this->layanan->perpanjang(
                $request->user(),
                $rental->id,
                CarbonImmutable::parse($request->validated('tanggal_selesai_baru')),
                $request->file('bukti_bayar')
            );
        } catch (AturanSewaException $e) {
            return back()->withInput()->withErrors(['perpanjang' => $e->getMessage()]);
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors([
                'perpanjang' => 'Terjadi bentrokan data karena ada permintaan bersamaan. Silakan kirim ulang.',
            ]);
        }

        $pesan = "Perpanjangan berhasil. Nomor {$p->nomor_perpanjang}: garasi {$rental->garage_id} "
            ."diperpanjang {$p->tambahan_hari} hari sampai ".$p->tanggal_selesai_baru->format('d/m/Y')
            .', biaya tambahan Rp '.number_format((float) $p->biaya_perpanjangan, 0, ',', '.').'.';

        return redirect()->route('admin.extensions.index')->with('status', $pesan);
    }
}
