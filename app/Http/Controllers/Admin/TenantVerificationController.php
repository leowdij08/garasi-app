<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusVerifikasi;
use App\Exceptions\PendaftaranSudahDiputuskanException;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantVerificationController extends Controller
{
    /** Daftar pendaftar; bawaan menampilkan yang masih menunggu. */
    public function index(Request $request): View
    {
        $filter = (string) $request->query('status', 'PENDING');
        $status = StatusVerifikasi::tryFrom($filter);

        $daftar = Tenant::with('user')
            ->when($status, fn ($query) => $query->where('status_verifikasi', $status->value))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $jumlah = Tenant::query()->toBase()
            ->selectRaw('status_verifikasi, count(*) as total')
            ->groupBy('status_verifikasi')
            ->pluck('total', 'status_verifikasi');

        return view('admin.tenants.index', compact('daftar', 'jumlah', 'filter'));
    }

    public function setujui(Request $request, Tenant $tenant): RedirectResponse
    {
        return $this->putuskan($request, $tenant, StatusVerifikasi::APPROVED, 'disetujui');
    }

    public function tolak(Request $request, Tenant $tenant): RedirectResponse
    {
        return $this->putuskan($request, $tenant, StatusVerifikasi::REJECTED, 'ditolak');
    }

    private function putuskan(Request $request, Tenant $tenant, StatusVerifikasi $hasil, string $kata): RedirectResponse
    {
        try {
            $tenant->tentukanVerifikasi($hasil, $request->user());
        } catch (PendaftaranSudahDiputuskanException $e) {
            return back()->withErrors(['verifikasi' => $e->getMessage()]);
        }

        return back()->with('status', "Pendaftaran {$tenant->nama_lengkap} {$kata}.");
    }
}