<?php

namespace App\Services;

use App\Enums\TipeGarasi;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Laporan sewa garasi (poin 18).
 * Sewa dikelompokkan menurut tanggal mulai. Pendapatan = total biaya sewa + biaya perpanjangan.
 */
class ReportService
{
    public const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private const KOLOM = 'COUNT(r.id) AS jumlah, COALESCE(SUM(r.total_biaya), 0) AS biaya_sewa, COALESCE(SUM(e.total), 0) AS biaya_perpanjangan';

    /** Satu baris per sewa, digabung dengan jumlah biaya perpanjangannya (sudah dijumlah per sewa agar tidak terhitung ganda). */
    private function dasar(): Builder
    {
        $perpanjangan = DB::table('rental_extensions')
            ->selectRaw('rental_id, SUM(biaya_perpanjangan) AS total')
            ->groupBy('rental_id');

        return DB::table('rentals as r')
            ->leftJoinSub($perpanjangan, 'e', 'e.rental_id', '=', 'r.id');
    }

    private function periode(Builder $query, ?int $tahun, ?int $bulan): Builder
    {
        if ($tahun !== null) {
            $query->whereYear('r.tanggal_mulai', $tahun);

            if ($bulan !== null) {
                $query->whereMonth('r.tanggal_mulai', $bulan);
            }
        }

        return $query;
    }

    /** @return array{baris: list<array<string, mixed>>, total: array<string, int|float>} */
    public function perBulan(int $tahun): array
    {
        $data = $this->periode($this->dasar(), $tahun, null)
            ->selectRaw('MONTH(r.tanggal_mulai) AS periode, '.self::KOLOM)
            ->groupByRaw('MONTH(r.tanggal_mulai)')
            ->get()->keyBy('periode');

        $baris = [];
        foreach (self::BULAN as $no => $nama) {
            $baris[] = $this->baris($nama, $data->get($no));
        }

        return $this->hasil($baris);
    }

    /** @return array{baris: list<array<string, mixed>>, total: array<string, int|float>} */
    public function perHari(int $tahun, int $bulan): array
    {
        $data = $this->periode($this->dasar(), $tahun, $bulan)
            ->selectRaw('DAY(r.tanggal_mulai) AS periode, '.self::KOLOM)
            ->groupByRaw('DAY(r.tanggal_mulai)')
            ->get()->keyBy('periode');

        $jumlahHari = CarbonImmutable::create($tahun, $bulan, 1)->daysInMonth;

        $baris = [];
        for ($hari = 1; $hari <= $jumlahHari; $hari++) {
            $baris[] = $this->baris(sprintf('%02d/%02d/%d', $hari, $bulan, $tahun), $data->get($hari));
        }

        return $this->hasil($baris);
    }

    /** @return array{baris: list<array<string, mixed>>, total: array<string, int|float>} */
    public function perTipeGarasi(?int $tahun, ?int $bulan): array
    {
        $data = $this->periode($this->dasar(), $tahun, $bulan)
            ->join('garages as g', 'g.id', '=', 'r.garage_id')
            ->selectRaw('g.tipe_garasi AS periode, '.self::KOLOM)
            ->groupBy('g.tipe_garasi')
            ->get()->keyBy('periode');

        $baris = [];
        foreach (TipeGarasi::cases() as $tipe) {
            $baris[] = $this->baris($tipe->label(), $data->get($tipe->value));
        }

        return $this->hasil($baris);
    }

    /** Hanya penyewa yang punya sewa pada periode tersebut, diurutkan dari pendapatan terbesar. */
    public function perPenyewa(?int $tahun, ?int $bulan): array
    {
        $data = $this->periode($this->dasar(), $tahun, $bulan)
            ->join('tenants as t', 't.id', '=', 'r.tenant_id')
            ->selectRaw('t.id, t.nama_lengkap, t.nik, '.self::KOLOM)
            ->groupBy('t.id', 't.nama_lengkap', 't.nik')
            ->orderByRaw('(COALESCE(SUM(r.total_biaya), 0) + COALESCE(SUM(e.total), 0)) DESC')
            ->get();

        $baris = $data->map(fn ($row) => $this->baris($row->nama_lengkap, $row, 'NIK '.$row->nik))->all();

        return $this->hasil($baris);
    }

    /** @return array<string, mixed> */
    private function baris(string $label, ?object $row, ?string $sub = null): array
    {
        $sewa = round((float) ($row->biaya_sewa ?? 0), 2);
        $perpanjangan = round((float) ($row->biaya_perpanjangan ?? 0), 2);

        return [
            'label' => $label,
            'sub' => $sub,
            'jumlah' => (int) ($row->jumlah ?? 0),
            'sewa' => $sewa,
            'perpanjangan' => $perpanjangan,
            'pendapatan' => round($sewa + $perpanjangan, 2),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $baris
     * @return array{baris: list<array<string, mixed>>, total: array<string, int|float>}
     */
    private function hasil(array $baris): array
    {
        $total = ['jumlah' => 0, 'sewa' => 0.0, 'perpanjangan' => 0.0, 'pendapatan' => 0.0];

        foreach ($baris as $b) {
            foreach (array_keys($total) as $kolom) {
                $total[$kolom] += $b[$kolom];
            }
        }

        foreach (['sewa', 'perpanjangan', 'pendapatan'] as $kolom) {
            $total[$kolom] = round($total[$kolom], 2);
        }

        return ['baris' => $baris, 'total' => $total];
    }
}
