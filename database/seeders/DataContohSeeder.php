<?php

namespace Database\Seeders;

use App\Enums\StatusSewa;
use App\Enums\TipeKendaraan;
use App\Enums\TipeSewa;
use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DocumentNumberGenerator;
use App\Services\PricingService;
use App\Services\RentalTypeResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * DATA CONTOH untuk demo laporan: sewa lampau (Januari-Oktober 2026), seluruhnya berstatus SELESAI.
 * Dijalankan manual: php artisan db:seed --class=DataContohSeeder
 * Memerlukan garasi C001, C002, E001, E002 (dan S001 untuk satu baris); baris yang garasinya tidak ada dilewati.
 */
class DataContohSeeder extends Seeder
{
    /** [garasi, email penyewa, tipe kendaraan, plat, merek, model, tipe sewa, tanggal mulai, jumlah hari, sisa baterai, tambahan hari perpanjangan] */
    private const DATA = [
        ['C001', 'penyewa@garasi.test', 'MOBIL', 'B1001AA', 'Toyota', 'Avanza', 'HARIAN', '2026-01-05', 3, null, 2],
        ['C002', 'pending@garasi.test', 'MOBIL', 'B1002BB', 'Honda', 'Brio', 'HARIAN', '2026-02-10', 5, null, 0],
        ['E001', 'penyewa@garasi.test', 'MOBIL_LISTRIK', 'B2001CC', 'Hyundai', 'Ioniq 5', 'BULANAN', '2026-03-01', 0, 20, 0],
        ['C001', 'pending@garasi.test', 'MOBIL', 'B1003DD', 'Suzuki', 'Ertiga', 'HARIAN', '2026-03-12', 7, null, 0],
        ['S001', 'penyewa@garasi.test', 'SEPEDA_MOTOR', 'B3001FF', 'Honda', 'Vario', 'HARIAN', '2026-04-20', 12, null, 0],
        ['E002', 'pending@garasi.test', 'MOBIL_LISTRIK', 'B2002EE', 'Wuling', 'Air EV', 'HARIAN', '2026-05-03', 4, 10, 0],
        ['C002', 'penyewa@garasi.test', 'MOBIL', 'B1001AA', 'Toyota', 'Avanza', 'HARIAN', '2026-06-15', 10, null, 0],
        ['C001', 'penyewa@garasi.test', 'MOBIL', 'B1001AA', 'Toyota', 'Avanza', 'BULANAN', '2026-08-01', 0, null, 0],
        ['E001', 'pending@garasi.test', 'MOBIL_LISTRIK', 'B2002EE', 'Wuling', 'Air EV', 'HARIAN', '2026-09-02', 6, 30, 2],
        ['C002', 'pending@garasi.test', 'MOBIL', 'B1003DD', 'Suzuki', 'Ertiga', 'HARIAN', '2026-10-05', 8, null, 3],
    ];

    public function run(PricingService $harga, RentalTypeResolver $resolver, DocumentNumberGenerator $nomor): void
    {
        $admin = User::where('email', 'admin@garasi.test')->first();
        $penyewa = Tenant::with('user')->get()->keyBy(fn (Tenant $t) => $t->user->email);

        if ($admin === null || $penyewa->isEmpty()) {
            $this->command->error('Jalankan "php artisan db:seed" lebih dulu agar akun contoh tersedia.');

            return;
        }

        $dibuat = 0;
        $dilewati = 0;

        foreach (self::DATA as [$garasiId, $email, $tipeKendaraan, $plat, $merek, $model, $tipeSewa, $tanggal, $hari, $sisa, $tambahan]) {
            $garasi = Garage::find($garasiId);
            $tenant = $penyewa->get($email);

            if ($garasi === null || $tenant === null
                || Rental::where('garage_id', $garasiId)->whereDate('tanggal_mulai', $tanggal)->exists()) {
                $dilewati++;

                continue;
            }

            $tipeKend = TipeKendaraan::from($tipeKendaraan);
            $mulai = CarbonImmutable::parse($tanggal);
            $hasil = $resolver->resolve($mulai, TipeSewa::from($tipeSewa), $hari);

            $kendaraan = Vehicle::firstOrCreate(
                ['nomor_plat' => $plat],
                [
                    'tenant_id' => $tenant->id,
                    'tipe_kendaraan' => $tipeKend,
                    'merek' => $merek,
                    'model' => $model,
                    'kapasitas_baterai_kwh' => $tipeKend === TipeKendaraan::MOBIL_LISTRIK ? 60 : null,
                ]
            );

            $biayaSewa = $harga->biayaSewa($hasil->tipeSewa, $hasil->lamaHari, $garasi->harga_sewa_harian);
            $biayaListrik = 0.0;
            $hargaKwh = null;
            $sisaBaterai = null;

            if ($harga->berlakuBiayaListrik($tipeKend, $garasi->tipe_garasi)) {
                $hargaKwh = (float) $garasi->harga_listrik_kwh;
                $sisaBaterai = (float) $sisa;
                $biayaListrik = $harga->biayaListrik($kendaraan->kapasitas_baterai_kwh, $sisaBaterai, $hargaKwh);
            }

            $total = round($biayaSewa + $biayaListrik, 2);

            $sewa = Rental::create([
                'nomor_nota' => $nomor->nomorNota($mulai),
                'tenant_id' => $tenant->id,
                'garage_id' => $garasi->id,
                'vehicle_id' => $kendaraan->id,
                'tipe_sewa' => $hasil->tipeSewa,
                'jumlah_hari' => $hasil->jumlahHari,
                'tanggal_mulai' => $hasil->tanggalMulai,
                'tanggal_selesai' => $hasil->tanggalSelesai,
                'harga_sewa_harian' => $garasi->harga_sewa_harian,
                'biaya_sewa' => $biayaSewa,
                'harga_listrik_kwh' => $hargaKwh,
                'sisa_baterai_kwh' => $sisaBaterai,
                'biaya_listrik' => $biayaListrik,
                'total_biaya' => $total,
                'status' => StatusSewa::SELESAI,
            ]);

            Payment::create([
                'rental_id' => $sewa->id,
                'rental_extension_id' => null,
                'bukti_gambar' => 'bukti-bayar/contoh.png',
                'jumlah' => $total,
                'diunggah_oleh' => $tenant->user_id,
            ]);

            if ($tambahan > 0) {
                $lama = $hasil->tanggalSelesai;
                $baru = $lama->addDays($tambahan);
                $biayaPerpanjangan = $harga->biayaPerpanjangan($garasi->harga_sewa_harian, $tambahan);

                $perpanjangan = RentalExtension::create([
                    'nomor_perpanjang' => $nomor->nomorPerpanjang($lama),
                    'rental_id' => $sewa->id,
                    'admin_id' => $admin->id,
                    'tanggal_selesai_lama' => $lama,
                    'tanggal_selesai_baru' => $baru,
                    'tambahan_hari' => $tambahan,
                    'biaya_perpanjangan' => $biayaPerpanjangan,
                ]);

                Payment::create([
                    'rental_id' => $sewa->id,
                    'rental_extension_id' => $perpanjangan->id,
                    'bukti_gambar' => 'bukti-bayar/contoh.png',
                    'jumlah' => $biayaPerpanjangan,
                    'diunggah_oleh' => $admin->id,
                ]);

                $sewa->update(['tanggal_selesai' => $baru]);
            }

            $dibuat++;
        }

        $this->command->info("Data contoh: {$dibuat} sewa dibuat, {$dilewati} dilewati.");
    }
}
