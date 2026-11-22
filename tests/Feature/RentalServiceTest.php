<?php

namespace Tests\Feature;

use App\Enums\StatusGarasi;
use App\Enums\StatusSewa;
use App\Enums\StatusVerifikasi;
use App\Enums\TipeGarasi;
use App\Enums\TipeSewa;
use App\Exceptions\DataSewaTidakValidException;
use App\Exceptions\GarasiTidakTersediaException;
use App\Exceptions\KendaraanSedangDisewaException;
use App\Exceptions\NomorPlatDipakaiPenyewaLainException;
use App\Exceptions\PenyewaBelumDiverifikasiException;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuatDataUji;
use Tests\TestCase;

class RentalServiceTest extends TestCase
{
    use BuatDataUji, RefreshDatabase;

    private RentalService $layanan;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->travelTo(CarbonImmutable::create(2026, 11, 22, 10));
        $this->layanan = app(RentalService::class);

        $this->buatGarasi('C001', TipeGarasi::MOBIL, 50000);
        $this->buatGarasi('C002', TipeGarasi::MOBIL, 50000);
        $this->buatGarasi('S001', TipeGarasi::SEPEDA, 10000);
        $this->buatGarasi('S002', TipeGarasi::SEPEDA, 10000);
        $this->buatGarasi('E001', TipeGarasi::LISTRIK, 80000, 3000);
    }

    private function sewa(Tenant $penyewa, array $ganti = []): Rental
    {
        [$sewa] = $this->layanan->buat($penyewa, $this->dataSewa($ganti), $this->bukti());

        return $sewa;
    }

    public function test_sewa_harian_tersimpan_lengkap(): void
    {
        $penyewa = $this->buatPenyewa();

        [$sewa, $hasil] = $this->layanan->buat($penyewa, $this->dataSewa(), $this->bukti());

        $this->assertSame('SEW-20261122-0001', $sewa->nomor_nota);
        $this->assertSame(TipeSewa::HARIAN, $sewa->tipe_sewa);
        $this->assertEquals(3, $sewa->jumlah_hari);
        $this->assertSame('2026-11-22', $sewa->tanggal_mulai->toDateString());
        $this->assertSame('2026-11-24', $sewa->tanggal_selesai->toDateString());
        $this->assertEquals(150000, (float) $sewa->total_biaya);
        $this->assertSame(StatusSewa::AKTIF, $sewa->status);
        $this->assertSame(StatusGarasi::USED, Garage::find('C001')->status);
        $this->assertNull($hasil->notifikasi);

        $bayar = $sewa->payments()->first();
        $this->assertEquals(150000, (float) $bayar->jumlah);
        $this->assertNull($bayar->rental_extension_id);
        $this->assertEquals($penyewa->user_id, $bayar->diunggah_oleh);
        Storage::disk('public')->assertExists($bayar->bukti_gambar);
    }

    public function test_nomor_plat_dinormalisasi_huruf_besar_tanpa_spasi(): void
    {
        $this->sewa($this->buatPenyewa(), ['nomor_plat' => 'd 1234 ab']);

        $this->assertDatabaseHas('vehicles', ['nomor_plat' => 'D1234AB']);
    }

    public function test_harian_30_hari_di_november_dikonversi_menjadi_bulanan(): void
    {
        [$sewa, $hasil] = $this->layanan->buat($this->buatPenyewa(), $this->dataSewa(['jumlah_hari' => 30]), $this->bukti());

        $this->assertSame(TipeSewa::BULANAN, $sewa->tipe_sewa);
        $this->assertEquals(0, $sewa->jumlah_hari);
        $this->assertSame('2026-12-21', $sewa->tanggal_selesai->toDateString());
        $this->assertEquals(1250000, (float) $sewa->total_biaya);
        $this->assertTrue($hasil->diubahOtomatis());
        $this->assertStringContainsString('Bulanan (30 hari)', $hasil->notifikasi);
    }

    public function test_sewa_bulanan_25_kali_harga_harian(): void
    {
        $sewa = $this->sewa($this->buatPenyewa(), ['tipe_sewa' => 'BULANAN', 'jumlah_hari' => null]);

        $this->assertEquals(1250000, (float) $sewa->biaya_sewa);
        $this->assertSame('2026-12-21', $sewa->tanggal_selesai->toDateString());
    }

    public function test_mobil_listrik_di_garasi_listrik_dikenai_biaya_isi_listrik(): void
    {
        $sewa = $this->sewa($this->buatPenyewa(), [
            'tipe_kendaraan' => 'MOBIL_LISTRIK', 'nomor_plat' => 'B5678CD', 'kapasitas_baterai_kwh' => 60,
            'tipe_sewa' => 'BULANAN', 'jumlah_hari' => null,
            'tipe_garasi' => 'LISTRIK', 'garage_id' => 'E001', 'sisa_baterai_kwh' => 20,
        ]);

        $this->assertEquals(2000000, (float) $sewa->biaya_sewa);
        $this->assertEquals(120000, (float) $sewa->biaya_listrik);
        $this->assertEquals(2120000, (float) $sewa->total_biaya);
        $this->assertEquals(20, (float) $sewa->sisa_baterai_kwh);
        $this->assertEquals(3000, (float) $sewa->harga_listrik_kwh);
    }

    public function test_mobil_listrik_di_garasi_mobil_tidak_dikenai_biaya_listrik(): void
    {
        $sewa = $this->sewa($this->buatPenyewa(), [
            'tipe_kendaraan' => 'MOBIL_LISTRIK', 'nomor_plat' => 'B5678CD', 'kapasitas_baterai_kwh' => 60,
            'jumlah_hari' => 2, 'tipe_garasi' => 'MOBIL', 'garage_id' => 'C001',
        ]);

        $this->assertEquals(0, (float) $sewa->biaya_listrik);
        $this->assertNull($sewa->sisa_baterai_kwh);
        $this->assertEquals(100000, (float) $sewa->total_biaya);
    }

    public function test_sisa_baterai_di_luar_rentang_ditolak_dan_berkas_dibersihkan(): void
    {
        $penyewa = $this->buatPenyewa();

        $this->harusDitolak(DataSewaTidakValidException::class, fn () => $this->layanan->buat($penyewa, $this->dataSewa([
            'tipe_kendaraan' => 'MOBIL_LISTRIK', 'nomor_plat' => 'B5678CD', 'kapasitas_baterai_kwh' => 60,
            'tipe_garasi' => 'LISTRIK', 'garage_id' => 'E001', 'sisa_baterai_kwh' => 61,
        ]), $this->bukti()));

        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(StatusGarasi::AVAILABLE, Garage::find('E001')->status);
    }

    public function test_penyewa_pending_tidak_boleh_menyewa(): void
    {
        $pending = $this->buatPenyewa(StatusVerifikasi::PENDING);

        $this->harusDitolak(PenyewaBelumDiverifikasiException::class, fn () => $this->layanan->buat($pending, $this->dataSewa(), $this->bukti()));

        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_kendaraan_tidak_boleh_di_garasi_yang_tidak_cocok(): void
    {
        $penyewa = $this->buatPenyewa();

        $this->harusDitolak(DataSewaTidakValidException::class, fn () => $this->layanan->buat($penyewa, $this->dataSewa([
            'tipe_kendaraan' => 'SEPEDA_MOTOR', 'nomor_plat' => 'B1234XY', 'tipe_garasi' => 'MOBIL', 'garage_id' => 'C001',
        ]), $this->bukti()));

        $this->harusDitolak(DataSewaTidakValidException::class, fn () => $this->layanan->buat($penyewa, $this->dataSewa([
            'tipe_kendaraan' => 'MOBIL', 'tipe_garasi' => 'LISTRIK', 'garage_id' => 'E001',
        ]), $this->bukti()));
    }

    public function test_garasi_yang_sama_tidak_bisa_disewa_dua_penyewa_pada_rentang_beririsan(): void
    {
        $this->sewa($this->buatPenyewa());
        $penyewaLain = $this->buatPenyewa();

        $this->harusDitolak(GarasiTidakTersediaException::class, fn () => $this->layanan->buat(
            $penyewaLain, $this->dataSewa(['nomor_plat' => 'B9999ZZ']), $this->bukti()
        ));

        $this->assertDatabaseCount('rentals', 1);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_kendaraan_yang_sama_tidak_boleh_aktif_di_dua_garasi(): void
    {
        $penyewa = $this->buatPenyewa();
        $this->sewa($penyewa);

        $this->harusDitolak(KendaraanSedangDisewaException::class, fn () => $this->layanan->buat(
            $penyewa, $this->dataSewa(['nomor_plat' => 'd 1234 ab', 'garage_id' => 'C002']), $this->bukti()
        ));

        $this->assertDatabaseCount('rentals', 1);
    }

    public function test_plat_milik_penyewa_lain_ditolak(): void
    {
        $this->sewa($this->buatPenyewa());
        $penyewaLain = $this->buatPenyewa();

        $this->harusDitolak(NomorPlatDipakaiPenyewaLainException::class, fn () => $this->layanan->buat(
            $penyewaLain, $this->dataSewa(['garage_id' => 'C002']), $this->bukti()
        ));
    }

    public function test_satu_penyewa_boleh_menyewa_dua_garasi_dengan_kendaraan_berbeda(): void
    {
        $penyewa = $this->buatPenyewa();

        $this->sewa($penyewa);
        $this->sewa($penyewa, ['nomor_plat' => 'B2222BB', 'garage_id' => 'C002']);

        $this->assertDatabaseCount('rentals', 2);
        $this->assertDatabaseCount('vehicles', 2);
    }

    public function test_sepeda_tidak_wajib_plat(): void
    {
        $this->sewa($this->buatPenyewa(), [
            'tipe_kendaraan' => 'SEPEDA', 'nomor_plat' => null, 'tipe_garasi' => 'SEPEDA', 'garage_id' => 'S001',
        ]);

        $this->assertNull(Vehicle::first()->nomor_plat);
    }

    public function test_mobil_tanpa_plat_ditolak(): void
    {
        $penyewa = $this->buatPenyewa();

        $this->harusDitolak(DataSewaTidakValidException::class, fn () => $this->layanan->buat(
            $penyewa, $this->dataSewa(['nomor_plat' => null]), $this->bukti()
        ));
    }

    public function test_kendaraan_lama_milik_penyewa_yang_sama_dipakai_ulang_dan_diperbarui(): void
    {
        $penyewa = $this->buatPenyewa();
        $pertama = $this->sewa($penyewa);

        // Sewa pertama dianggap sudah berakhir.
        $pertama->update(['tanggal_mulai' => '2026-11-01', 'tanggal_selesai' => '2026-11-03', 'status' => 'SELESAI']);

        $this->sewa($penyewa, ['merek' => 'Honda', 'model' => 'Brio']);

        $this->assertDatabaseCount('vehicles', 1);
        $this->assertSame('Honda', Vehicle::first()->merek);
        $this->assertSame('Brio', Vehicle::first()->model);
    }

    public function test_sepeda_tanpa_plat_selalu_dibuat_baru(): void
    {
        $penyewa = $this->buatPenyewa();
        $sepeda = ['tipe_kendaraan' => 'SEPEDA', 'nomor_plat' => null, 'tipe_garasi' => 'SEPEDA'];

        $this->sewa($penyewa, $sepeda + ['garage_id' => 'S001']);
        $this->sewa($penyewa, $sepeda + ['garage_id' => 'S002']);

        $this->assertDatabaseCount('vehicles', 2);
    }
}
