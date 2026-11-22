<?php

namespace Tests\Feature;

use App\Enums\StatusGarasi;
use App\Enums\TipeGarasi;
use App\Exceptions\KuotaGarasiTidakCukupException;
use App\Models\Garage;
use App\Services\GarageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuatDataUji;
use Tests\TestCase;

class GarageServiceTest extends TestCase
{
    use BuatDataUji, RefreshDatabase;

    private GarageService $layanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->layanan = app(GarageService::class);
    }

    public function test_batch_pertama_membuat_kode_berurutan(): void
    {
        [$awal, $akhir] = $this->layanan->buatBatch(TipeGarasi::SEPEDA, 3, 10000, null);

        $this->assertSame('S001', $awal);
        $this->assertSame('S003', $akhir);
        $this->assertDatabaseCount('garages', 3);
    }

    public function test_batch_berikutnya_melanjutkan_nomor(): void
    {
        $this->layanan->buatBatch(TipeGarasi::SEPEDA, 3, 10000, null);
        [$awal, $akhir] = $this->layanan->buatBatch(TipeGarasi::SEPEDA, 2, 10000, null);

        $this->assertSame('S004', $awal);
        $this->assertSame('S005', $akhir);
    }

    public function test_nomor_urut_dihitung_terpisah_per_tipe(): void
    {
        $this->layanan->buatBatch(TipeGarasi::SEPEDA, 2, 10000, null);
        [$mobil] = $this->layanan->buatBatch(TipeGarasi::MOBIL, 2, 50000, null);
        [$listrik] = $this->layanan->buatBatch(TipeGarasi::LISTRIK, 1, 80000, 3000);

        $this->assertSame('C001', $mobil);
        $this->assertSame('E001', $listrik);
    }

    public function test_sisa_kuota_dan_nomor_terakhir(): void
    {
        $this->layanan->buatBatch(TipeGarasi::SEPEDA, 5, 10000, null);

        $this->assertSame(5, $this->layanan->nomorTerakhir(TipeGarasi::SEPEDA));
        $this->assertSame(994, $this->layanan->sisaKuota(TipeGarasi::SEPEDA));
        $this->assertSame(999, $this->layanan->sisaKuota(TipeGarasi::MOBIL));
    }

    public function test_kuota_999_per_tipe_bukan_total_semua_tipe(): void
    {
        $this->layanan->buatBatch(TipeGarasi::SEPEDA, 999, 10000, null);

        $this->assertDatabaseHas('garages', ['id' => 'S999']);
        $this->assertSame(0, $this->layanan->sisaKuota(TipeGarasi::SEPEDA));

        [$mobil] = $this->layanan->buatBatch(TipeGarasi::MOBIL, 1, 50000, null);
        $this->assertSame('C001', $mobil);

        $this->harusDitolak(KuotaGarasiTidakCukupException::class, fn () => $this->layanan->buatBatch(TipeGarasi::SEPEDA, 1, 10000, null));
    }

    public function test_jumlah_melebihi_sisa_kuota_tidak_membuat_garasi_apa_pun(): void
    {
        $this->layanan->buatBatch(TipeGarasi::SEPEDA, 990, 10000, null);

        try {
            $this->layanan->buatBatch(TipeGarasi::SEPEDA, 10, 10000, null);
            $this->fail('Seharusnya ditolak karena sisa kuota hanya 9.');
        } catch (KuotaGarasiTidakCukupException $e) {
            $this->assertSame(9, $e->sisa);
        }

        $this->assertDatabaseCount('garages', 990);
    }

    public function test_harga_listrik_hanya_disimpan_untuk_garasi_listrik(): void
    {
        $this->layanan->buatBatch(TipeGarasi::MOBIL, 1, 50000, 3000);
        $this->layanan->buatBatch(TipeGarasi::LISTRIK, 1, 80000, 3000);

        $this->assertNull(Garage::find('C001')->harga_listrik_kwh);
        $this->assertEquals(3000, (float) Garage::find('E001')->harga_listrik_kwh);
    }

    public function test_garasi_baru_berstatus_available(): void
    {
        $this->layanan->buatBatch(TipeGarasi::MOBIL, 2, 50000, null);

        $this->assertSame(StatusGarasi::AVAILABLE, Garage::find('C001')->status);
        $this->assertSame(StatusGarasi::AVAILABLE, Garage::find('C002')->status);
    }
}
