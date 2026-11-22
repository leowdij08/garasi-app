<?php

namespace Tests\Feature;

use App\Enums\TipeGarasi;
use App\Models\Tenant;
use App\Services\RentalExtensionService;
use App\Services\RentalService;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuatDataUji;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use BuatDataUji, RefreshDatabase;

    private Tenant $penyewaA;

    private Tenant $penyewaB;

    private ReportService $laporan;

    /**
     * Data uji:
     * - 10 Mar: penyewa A, C001, 4 hari = 200.000; diperpanjang dua kali (masing-masing 2 hari = 100.000)
     * - 20 Mar: penyewa B, S001, 5 hari = 50.000
     * - 2 Apr: penyewa A, E001 bulanan = 2.000.000 + listrik 120.000
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->buatGarasi('C001', TipeGarasi::MOBIL, 50000);
        $this->buatGarasi('S001', TipeGarasi::SEPEDA, 10000);
        $this->buatGarasi('E001', TipeGarasi::LISTRIK, 80000, 3000);

        $admin = $this->buatAdmin();
        $this->penyewaA = $this->buatPenyewa();
        $this->penyewaB = $this->buatPenyewa();

        $sewa = app(RentalService::class);
        $perpanjang = app(RentalExtensionService::class);

        $this->travelTo(CarbonImmutable::create(2026, 3, 10, 9));
        [$sewaA] = $sewa->buat($this->penyewaA, $this->dataSewa(['jumlah_hari' => 4]), $this->bukti());

        $this->travelTo(CarbonImmutable::create(2026, 3, 12, 9));
        $perpanjang->perpanjang($admin, $sewaA->id, CarbonImmutable::create(2026, 3, 15), $this->bukti());

        $this->travelTo(CarbonImmutable::create(2026, 3, 14, 9));
        $perpanjang->perpanjang($admin, $sewaA->id, CarbonImmutable::create(2026, 3, 17), $this->bukti());

        $this->travelTo(CarbonImmutable::create(2026, 3, 20, 9));
        $sewa->buat($this->penyewaB, $this->dataSewa([
            'tipe_kendaraan' => 'SEPEDA', 'nomor_plat' => null, 'merek' => 'Polygon', 'model' => 'Heist',
            'jumlah_hari' => 5, 'tipe_garasi' => 'SEPEDA', 'garage_id' => 'S001',
        ]), $this->bukti());

        $this->travelTo(CarbonImmutable::create(2026, 4, 2, 9));
        $sewa->buat($this->penyewaA, $this->dataSewa([
            'tipe_kendaraan' => 'MOBIL_LISTRIK', 'nomor_plat' => 'B5678CD', 'kapasitas_baterai_kwh' => 60,
            'tipe_sewa' => 'BULANAN', 'jumlah_hari' => null,
            'tipe_garasi' => 'LISTRIK', 'garage_id' => 'E001', 'sisa_baterai_kwh' => 20,
        ]), $this->bukti());

        $this->laporan = app(ReportService::class);
    }

    public function test_laporan_per_bulan_menjumlahkan_sewa_dan_perpanjangan(): void
    {
        $hasil = $this->laporan->perBulan(2026);
        $maret = $hasil['baris'][2];
        $april = $hasil['baris'][3];

        $this->assertSame('Maret', $maret['label']);
        $this->assertEquals(2, $maret['jumlah']);
        $this->assertEquals(250000, $maret['sewa']);
        $this->assertEquals(200000, $maret['perpanjangan']);
        $this->assertEquals(450000, $maret['pendapatan']);

        $this->assertEquals(1, $april['jumlah']);
        $this->assertEquals(2120000, $april['sewa']);
        $this->assertEquals(0, $april['perpanjangan']);

        $this->assertEquals(0, $hasil['baris'][0]['jumlah']);
        $this->assertEquals(3, $hasil['total']['jumlah']);
        $this->assertEquals(2570000, $hasil['total']['pendapatan']);
    }

    public function test_dua_perpanjangan_tidak_membuat_sewa_terhitung_ganda(): void
    {
        $maret = $this->laporan->perBulan(2026)['baris'][2];

        $this->assertEquals(2, $maret['jumlah']);
    }

    public function test_laporan_per_hari_dikelompokkan_menurut_tanggal_mulai(): void
    {
        $hasil = $this->laporan->perHari(2026, 3);

        $this->assertCount(31, $hasil['baris']);
        $this->assertSame('10/03/2026', $hasil['baris'][9]['label']);
        $this->assertEquals(1, $hasil['baris'][9]['jumlah']);
        $this->assertEquals(1, $hasil['baris'][19]['jumlah']);
        $this->assertEquals(0, $hasil['baris'][10]['jumlah']);
        $this->assertEquals(2, $hasil['total']['jumlah']);
        $this->assertEquals(450000, $hasil['total']['pendapatan']);
    }

    public function test_laporan_per_tipe_garasi(): void
    {
        $hasil = $this->laporan->perTipeGarasi(2026, null);
        [$sepeda, $mobil, $listrik] = $hasil['baris'];

        $this->assertEquals(50000, $sepeda['pendapatan']);
        $this->assertEquals(400000, $mobil['pendapatan']);
        $this->assertEquals(2120000, $listrik['pendapatan']);
        $this->assertEquals(2570000, $hasil['total']['pendapatan']);

        $april = $this->laporan->perTipeGarasi(2026, 4);
        $this->assertEquals(0, $april['baris'][0]['jumlah']);
        $this->assertEquals(2120000, $april['total']['pendapatan']);
    }

    public function test_laporan_per_penyewa_diurutkan_dari_pendapatan_terbesar(): void
    {
        $hasil = $this->laporan->perPenyewa(2026, null);

        $this->assertCount(2, $hasil['baris']);
        $this->assertSame($this->penyewaA->nama_lengkap, $hasil['baris'][0]['label']);
        $this->assertEquals(2520000, $hasil['baris'][0]['pendapatan']);
        $this->assertEquals(50000, $hasil['baris'][1]['pendapatan']);
        $this->assertEquals(2570000, $hasil['total']['pendapatan']);
    }

    public function test_periode_tanpa_sewa_menghasilkan_nol(): void
    {
        $hasil = $this->laporan->perBulan(2025);

        $this->assertEquals(0, $hasil['total']['jumlah']);
        $this->assertEquals(0, $hasil['total']['pendapatan']);
    }
}