<?php

namespace App\Services;

use App\Enums\StatusGarasi;
use App\Enums\StatusSewa;
use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use App\Enums\TipeSewa;
use App\Exceptions\AturanSewaException;
use App\Exceptions\DataSewaTidakValidException;
use App\Exceptions\GarasiTidakTersediaException;
use App\Exceptions\KendaraanSedangDisewaException;
use App\Exceptions\PenyewaBelumDiverifikasiException;
use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/** Alur Sewa Baru. Total = biaya sewa + biaya listrik, tanpa tambahan lain. */
class RentalService
{
    public function __construct(
        private readonly RentalTypeResolver $tipeSewa,
        private readonly PricingService $harga,
        private readonly VehicleResolver $kendaraan,
        private readonly GarageAvailabilityService $ketersediaan,
        private readonly DocumentNumberGenerator $nomor,
        private readonly PaymentProofStorage $bukti,
    ) {}

    /**
     * @param  array<string, mixed>  $data  isian form yang sudah divalidasi
     * @return array{0: Rental, 1: HasilSewa}
     *
     * @throws AturanSewaException jika melanggar aturan bisnis
     */
    public function buat(Tenant $penyewa, array $data, UploadedFile $buktiBayar): array
    {
        if (! $penyewa->bolehMenyewa()) {
            throw new PenyewaBelumDiverifikasiException;
        }

        $tipeKendaraan = TipeKendaraan::from($data['tipe_kendaraan']);
        $tipeGarasi = TipeGarasi::from($data['tipe_garasi']);
        $tipeSewa = TipeSewa::from($data['tipe_sewa']);
        $jumlahHari = (int) ($data['jumlah_hari'] ?? 0);

        if (! $tipeKendaraan->bolehDiGarasi($tipeGarasi)) {
            throw new DataSewaTidakValidException(
                $tipeKendaraan->label().' tidak boleh ditempatkan di '.$tipeGarasi->label().'.'
            );
        }

        if ($tipeSewa === TipeSewa::HARIAN && $jumlahHari < 1) {
            throw new DataSewaTidakValidException('Jumlah hari sewa harian minimal 1.');
        }

        // Tanggal mulai selalu hari ini, ditentukan sistem (poin 7).
        $hasil = $this->tipeSewa->resolve(CarbonImmutable::now()->startOfDay(), $tipeSewa, $jumlahHari);

        // Berkas disimpan lebih dulu dan dihapus kembali jika transaksi gagal (poin 31).
        $path = $this->bukti->simpan($buktiBayar);

        try {
            $sewa = DB::transaction(
                fn () => $this->simpan($penyewa, $data, $tipeKendaraan, $tipeGarasi, $hasil, $path)
            );
        } catch (Throwable $e) {
            $this->bukti->hapus($path);

            throw $e;
        }

        return [$sewa, $hasil];
    }

    /** @param  array<string, mixed>  $data */
    private function simpan(Tenant $penyewa, array $data, TipeKendaraan $tipeKendaraan, TipeGarasi $tipeGarasi, HasilSewa $hasil, string $path): Rental
    {
        // Kunci baris garasi sebagai pernyataan pertama transaksi, sehingga dua penyewa yang
        // memilih garasi yang sama bersamaan diproses bergantian (poin 9).
        $garasi = Garage::query()->whereKey($data['garage_id'])->lockForUpdate()->first();

        if ($garasi === null || $garasi->tipe_garasi !== $tipeGarasi) {
            throw new GarasiTidakTersediaException('Garasi tidak ditemukan atau tidak sesuai tipe yang dipilih.');
        }

        if ($this->ketersediaan->garasiBentrok($garasi->id, $hasil->tanggalMulai, $hasil->tanggalSelesai)) {
            throw new GarasiTidakTersediaException("Garasi {$garasi->id} sudah disewa pada rentang tanggal tersebut. Silakan pilih garasi lain.");
        }

        $kapasitasInput = isset($data['kapasitas_baterai_kwh']) && $data['kapasitas_baterai_kwh'] !== ''
            ? (float) $data['kapasitas_baterai_kwh']
            : null;

        $kendaraan = $this->kendaraan->resolve(
            $penyewa,
            $tipeKendaraan,
            $data['nomor_plat'] ?? null,
            $data['merek'],
            $data['model'],
            $kapasitasInput
        );

        if ($this->ketersediaan->kendaraanBentrok($kendaraan->id, $hasil->tanggalMulai, $hasil->tanggalSelesai)) {
            throw new KendaraanSedangDisewaException($kendaraan->nomor_plat ?? $kendaraan->merek.' '.$kendaraan->model);
        }

        $biayaSewa = $this->harga->biayaSewa($hasil->tipeSewa, $hasil->lamaHari, $garasi->harga_sewa_harian);

        $biayaListrik = 0.0;
        $hargaKwh = null;
        $sisa = null;

        if ($this->harga->berlakuBiayaListrik($tipeKendaraan, $tipeGarasi)) {
            if (! isset($data['sisa_baterai_kwh']) || $data['sisa_baterai_kwh'] === '') {
                throw new DataSewaTidakValidException('Sisa baterai wajib diisi untuk mobil listrik di garasi mobil listrik.');
            }

            $sisa = (float) $data['sisa_baterai_kwh'];
            $hargaKwh = (float) $garasi->harga_listrik_kwh;

            try {
                $biayaListrik = $this->harga->biayaListrik($kendaraan->kapasitas_baterai_kwh, $sisa, $hargaKwh);
            } catch (InvalidArgumentException $e) {
                throw new DataSewaTidakValidException($e->getMessage());
            }
        }

        $total = round($biayaSewa + $biayaListrik, 2);

        $sewa = Rental::create([
            'nomor_nota' => $this->nomor->nomorNota($hasil->tanggalMulai),
            'tenant_id' => $penyewa->id,
            'garage_id' => $garasi->id,
            'vehicle_id' => $kendaraan->id,
            'tipe_sewa' => $hasil->tipeSewa,
            'jumlah_hari' => $hasil->jumlahHari,
            'tanggal_mulai' => $hasil->tanggalMulai,
            'tanggal_selesai' => $hasil->tanggalSelesai,
            'harga_sewa_harian' => $garasi->harga_sewa_harian,
            'biaya_sewa' => $biayaSewa,
            'harga_listrik_kwh' => $hargaKwh,
            'sisa_baterai_kwh' => $sisa,
            'biaya_listrik' => $biayaListrik,
            'total_biaya' => $total,
            'status' => StatusSewa::AKTIF,
        ]);

        // Pembayaran dianggap sah saat transaksi tersimpan; jumlah diisi sistem (poin 27).
        Payment::create([
            'rental_id' => $sewa->id,
            'rental_extension_id' => null,
            'bukti_gambar' => $path,
            'jumlah' => $total,
            'diunggah_oleh' => $penyewa->user_id,
        ]);

        $garasi->update(['status' => StatusGarasi::USED]);

        return $sewa;
    }
}
