<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipeGarasi;
use App\Exceptions\KuotaGarasiTidakCukupException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGarageBatchRequest;
use App\Models\Garage;
use App\Services\GarageService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function __construct(private readonly GarageService $garages)
    {
    }

    /** Daftar garasi, dengan ringkasan kuota dan filter per tipe. */
    public function index(Request $request): View
    {
        $filter = TipeGarasi::tryFrom((string) $request->query('tipe'));

        $daftar = Garage::query()
            ->when($filter, fn ($query) => $query->where('tipe_garasi', $filter->value))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $ringkasan = collect(TipeGarasi::cases())->map(fn (TipeGarasi $tipe) => [
            'tipe' => $tipe,
            'jumlah' => $this->garages->nomorTerakhir($tipe),
            'sisa' => $this->garages->sisaKuota($tipe),
        ]);

        return view('admin.garages.index', compact('daftar', 'ringkasan', 'filter'));
    }

    /** Form pembuatan garasi berkelompok. */
    public function create(): View
    {
        $sisa = [];
        foreach (TipeGarasi::cases() as $tipe) {
            $sisa[$tipe->value] = $this->garages->sisaKuota($tipe);
        }

        return view('admin.garages.create', compact('sisa'));
    }

    /** Menyimpan satu batch garasi baru. */
    public function store(StoreGarageBatchRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tipe = TipeGarasi::from($data['tipe_garasi']);

        try {
            [$awal, $akhir] = $this->garages->buatBatch(
                $tipe,
                (int) $data['jumlah'],
                $data['harga_sewa_harian'],
                $data['harga_listrik_kwh'] ?? null
            );
        } catch (KuotaGarasiTidakCukupException $e) {
            return back()->withInput()->withErrors(['jumlah' => $e->getMessage()]);
        } catch (UniqueConstraintViolationException) {
            // Dua permintaan bersamaan menghasilkan ID yang sama; PK menolak yang kedua (poin 14).
            return back()->withInput()->withErrors([
                'jumlah' => 'Terjadi bentrokan ID garasi karena ada permintaan bersamaan. Silakan coba lagi.',
            ]);
        }

        $pesan = $awal === $akhir
            ? "Garasi {$awal} berhasil dibuat."
            : "Garasi {$awal} sampai {$akhir} berhasil dibuat.";

        return redirect()->route('admin.garages.index')->with('status', $pesan);
    }
}