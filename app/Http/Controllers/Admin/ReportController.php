<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin: laporan sewa per bulan, per hari, per tipe garasi, dan per penyewa.
 */
class ReportController extends Controller
{
    public function index(Request $request, ReportService $laporan): View
    {
        $data = $request->validate([
            'jenis' => ['nullable', Rule::in(['bulan', 'hari', 'tipe', 'penyewa'])],
            'tahun' => ['nullable', 'integer', 'between:2000,2100', 'required_with:bulan'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
        ], [
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.between' => 'Tahun harus di antara 2000 dan 2100.',
            'tahun.required_with' => 'Tahun wajib diisi jika bulan dipilih.',
            'bulan.between' => 'Bulan tidak valid.',
        ]);

        $jenis = $data['jenis'] ?? 'bulan';
        $tahun = isset($data['tahun']) ? (int) $data['tahun'] : null;
        $bulan = isset($data['bulan']) ? (int) $data['bulan'] : null;

        // Laporan per bulan dan per hari selalu butuh periode; bawaannya tahun dan bulan sekarang.
        if (in_array($jenis, ['bulan', 'hari'], true)) {
            $tahun ??= now()->year;
        }
        if ($jenis === 'hari') {
            $bulan ??= now()->month;
        }

        $hasil = match ($jenis) {
            'bulan' => $laporan->perBulan($tahun),
            'hari' => $laporan->perHari($tahun, $bulan),
            'tipe' => $laporan->perTipeGarasi($tahun, $bulan),
            'penyewa' => $laporan->perPenyewa($tahun, $bulan),
        };

        $periode = match (true) {
            $tahun === null => 'Semua periode',
            $jenis === 'bulan' || $bulan === null => 'Tahun '.$tahun,
            default => ReportService::BULAN[$bulan].' '.$tahun,
        };

        return view('admin.reports.index', compact('jenis', 'tahun', 'bulan', 'hasil', 'periode'));
    }
}
