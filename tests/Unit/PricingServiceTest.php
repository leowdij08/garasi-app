<?php

namespace Tests\Unit;

use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use App\Enums\TipeSewa;
use App\Services\PricingService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    private PricingService $harga;

    protected function setUp(): void
    {
        parent::setUp();
        $this->harga = new PricingService();
    }

    public function test_bulanan_25_kali_harga_harian_tanpa_bergantung_hari_bulan(): void
    {
        $this->assertEquals(1250000.0, $this->harga->biayaSewa(TipeSewa::BULANAN, 28, 50000));
        $this->assertEquals(1250000.0, $this->harga->biayaSewa(TipeSewa::BULANAN, 31, 50000));
    }

    public function test_harian_dikali_jumlah_hari(): void
    {
        $this->assertEquals(30000.0, $this->harga->biayaSewa(TipeSewa::HARIAN, 3, 10000));
    }

    public function test_harian_30_hari_lebih_mahal_dari_bulanan_sesuai_catatan_konfirmasi(): void
    {
        $harian30 = $this->harga->biayaSewa(TipeSewa::HARIAN, 30, 10000);
        $bulanan = $this->harga->biayaSewa(TipeSewa::BULANAN, 31, 10000);

        $this->assertEquals(300000.0, $harian30);
        $this->assertEquals(250000.0, $bulanan);
        $this->assertGreaterThan($bulanan, $harian30);
    }

    public function test_biaya_listrik_kapasitas_dikurangi_sisa_dikali_harga_kwh(): void
    {
        $this->assertEquals(120000.0, $this->harga->biayaListrik(60, 20, 3000));
    }

    public function test_baterai_penuh_tidak_ada_biaya_listrik(): void
    {
        $this->assertEquals(0.0, $this->harga->biayaListrik(60, 60, 3000));
    }

    public function test_baterai_kosong_dikenai_biaya_penuh(): void
    {
        $this->assertEquals(180000.0, $this->harga->biayaListrik(60, 0, 3000));
    }

    public function test_sisa_baterai_melebihi_kapasitas_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->harga->biayaListrik(60, 61, 3000);
    }

    public function test_sisa_baterai_negatif_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->harga->biayaListrik(60, -1, 3000);
    }

    public function test_biaya_perpanjangan_dihitung_harian(): void
    {
        $this->assertEquals(200000.0, $this->harga->biayaPerpanjangan(50000, 4));
    }

    public function test_perpanjangan_nol_hari_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->harga->biayaPerpanjangan(50000, 0);
    }

    public function test_biaya_listrik_hanya_mobil_listrik_di_garasi_listrik(): void
    {
        $this->assertTrue($this->harga->berlakuBiayaListrik(TipeKendaraan::MOBIL_LISTRIK, TipeGarasi::LISTRIK));
        $this->assertFalse($this->harga->berlakuBiayaListrik(TipeKendaraan::MOBIL_LISTRIK, TipeGarasi::MOBIL));
        $this->assertFalse($this->harga->berlakuBiayaListrik(TipeKendaraan::MOBIL, TipeGarasi::MOBIL));
    }
}