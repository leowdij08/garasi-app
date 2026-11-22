<?php

namespace Tests\Feature;

use App\Enums\StatusGarasi;
use App\Enums\StatusSewa;
use App\Enums\TipeGarasi;
use App\Models\Garage;
use App\Services\GarageStatusSynchronizer;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuatDataUji;
use Tests\TestCase;

class GarageStatusSynchronizerTest extends TestCase
{
    use BuatDataUji, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->travelTo(CarbonImmutable::create(2026, 11, 22, 10));
        $this->buatGarasi('C001', TipeGarasi::MOBIL, 50000);
    }

    public function test_sewa_yang_sudah_lewat_menjadi_selesai_dan_garasi_kembali_available(): void
    {
        [$sewa] = app(RentalService::class)->buat($this->buatPenyewa(), $this->dataSewa(), $this->bukti()); // 22-24 Nov

        $this->travelTo(CarbonImmutable::create(2026, 11, 25, 8));
        [$jumlahSewa, $jumlahGarasi] = app(GarageStatusSynchronizer::class)->sinkron();

        $this->assertSame([1, 1], [$jumlahSewa, $jumlahGarasi]);
        $this->assertSame(StatusSewa::SELESAI, $sewa->fresh()->status);
        $this->assertSame(StatusGarasi::AVAILABLE, Garage::find('C001')->status);
    }

    public function test_hari_terakhir_bersifat_inklusif(): void
    {
        [$sewa] = app(RentalService::class)->buat($this->buatPenyewa(), $this->dataSewa(), $this->bukti()); // selesai 24 Nov

        $this->travelTo(CarbonImmutable::create(2026, 11, 24, 23, 30));
        [$jumlahSewa, $jumlahGarasi] = app(GarageStatusSynchronizer::class)->sinkron();

        $this->assertSame([0, 0], [$jumlahSewa, $jumlahGarasi]);
        $this->assertSame(StatusSewa::AKTIF, $sewa->fresh()->status);
        $this->assertSame(StatusGarasi::USED, Garage::find('C001')->status);
    }

    public function test_garasi_dengan_sewa_aktif_lain_tetap_used(): void
    {
        $layanan = app(RentalService::class);
        [$pertama] = $layanan->buat($this->buatPenyewa(), $this->dataSewa(), $this->bukti()); // 22-24 Nov

        $this->travelTo(CarbonImmutable::create(2026, 11, 25, 8));
        [$kedua] = $layanan->buat($this->buatPenyewa(), $this->dataSewa(['nomor_plat' => 'B2222BB', 'jumlah_hari' => 2]), $this->bukti()); // 25-26 Nov

        [$jumlahSewa, $jumlahGarasi] = app(GarageStatusSynchronizer::class)->sinkron();

        $this->assertSame([1, 0], [$jumlahSewa, $jumlahGarasi]);
        $this->assertSame(StatusSewa::SELESAI, $pertama->fresh()->status);
        $this->assertSame(StatusSewa::AKTIF, $kedua->fresh()->status);
        $this->assertSame(StatusGarasi::USED, Garage::find('C001')->status);
    }
}
