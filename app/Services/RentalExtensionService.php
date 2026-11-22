<?php

namespace App\Services;

use App\Enums\StatusSewa;
use App\Exceptions\AturanSewaException;
use App\Exceptions\PerpanjanganTidakValidException;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Alur perpanjangan sewa oleh admin (poin 17, 27, 29, 31). */
class RentalExtensionService
{
    /** Batas pengaman agar salah ketik tahun tidak menghasilkan tagihan yang tidak masuk akal. */
    public const MAKS_TAMBAHAN_HARI = 366;

    public function __construct(
        private readonly PricingService $harga,
        private readonly DocumentNumberGenerator $nomor,
        private readonly PaymentProofStorage $bukti,
    ) {}

    public function hitungTambahanHari(CarbonInterface $lama, CarbonInterface $baru): int
    {
        $a = CarbonImmutable::instance($lama)->startOfDay();
        $b = CarbonImmutable::instance($baru)->startOfDay();

        return (int) $a->diffInDays($b, false);
    }

    /** @throws AturanSewaException jika melanggar aturan perpanjangan */
    public function perpanjang(User $admin, int $rentalId, CarbonInterface $tanggalBaru, UploadedFile $buktiBayar): RentalExtension
    {
        // Berkas disimpan lebih dulu dan dihapus kembali jika transaksi gagal (poin 31).
        $path = $this->bukti->simpan($buktiBayar);

        try {
            return DB::transaction(fn () => $this->simpan($admin, $rentalId, $tanggalBaru, $path));
        } catch (Throwable $e) {
            $this->bukti->hapus($path);

            throw $e;
        }
    }

    private function simpan(User $admin, int $rentalId, CarbonInterface $tanggalBaru, string $path): RentalExtension
    {
        $sewa = Rental::query()->whereKey($rentalId)->lockForUpdate()->first();

        if ($sewa === null) {
            throw new PerpanjanganTidakValidException('Sewa tidak ditemukan.');
        }

        $hariIni = CarbonImmutable::today();

        if ($sewa->status !== StatusSewa::AKTIF || ! $sewa->aktifPada($hariIni)) {
            throw new PerpanjanganTidakValidException('Hanya sewa yang aktif pada hari ini yang dapat diperpanjang.');
        }

        $lama = CarbonImmutable::instance($sewa->tanggal_selesai)->startOfDay();
        $baru = CarbonImmutable::instance($tanggalBaru)->startOfDay();
        $tambahan = $this->hitungTambahanHari($lama, $baru);

        if ($tambahan < 1) {
            throw new PerpanjanganTidakValidException(
                'Tanggal selesai baru harus setelah tanggal selesai lama ('.$lama->format('d/m/Y').').'
            );
        }

        if ($tambahan > self::MAKS_TAMBAHAN_HARI) {
            throw new PerpanjanganTidakValidException(
                'Perpanjangan maksimal '.self::MAKS_TAMBAHAN_HARI.' hari sekali proses.'
            );
        }

        // Memakai harga harian yang tersimpan di sewa, bukan harga garasi saat ini.
        $biaya = $this->harga->biayaPerpanjangan($sewa->harga_sewa_harian, $tambahan);

        $perpanjangan = RentalExtension::create([
            'nomor_perpanjang' => $this->nomor->nomorPerpanjang($hariIni),
            'rental_id' => $sewa->id,
            'admin_id' => $admin->id,
            'tanggal_selesai_lama' => $lama,
            'tanggal_selesai_baru' => $baru,
            'tambahan_hari' => $tambahan,
            'biaya_perpanjangan' => $biaya,
        ]);

        // Pembayaran sah saat transaksi tersimpan; jumlah diisi sistem (poin 27).
        Payment::create([
            'rental_id' => $sewa->id,
            'rental_extension_id' => $perpanjangan->id,
            'bukti_gambar' => $path,
            'jumlah' => $biaya,
            'diunggah_oleh' => $admin->id,
        ]);

        $sewa->update(['tanggal_selesai' => $baru]);

        return $perpanjangan;
    }
}
