<?php

namespace Tests\Feature;

use App\Enums\TipeGarasi;
use App\Enums\TipeSewa;
use App\Exceptions\PerpanjanganTidakValidException;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Services\RentalExtensionService;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuatDataUji;
use Tests\TestCase;

class RentalExtensionServiceTest extends TestCase
{
    use BuatDataUji, RefreshDatabase;

    private RentalExtensionService $layanan;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->travelTo(CarbonImmutable::create(2026, 11, 22, 10));
        $this->layanan = app(RentalExtensionService::class);
        $this->admin = $this->buatAdmin();
        $this->buatGarasi('C001', TipeGarasi::MOBIL, 50000);
    }

    private function sewa(array $ganti = []): Rental
    {
        [$sewa] = app(RentalService::class)->buat($this->buatPenyewa(), $this->dataSewa($ganti), $this->bukti());

        return $sewa;
    }

    public function test_perpanjangan_harian_menghitung_biaya_dan_menggeser_tanggal_selesai(): void
    {
        $sewa = $this->sewa(); // 22-24 November

        $p = $this->layanan->perpanjang($this->admin, $sewa->id, CarbonImmutable::create(2026, 11, 27), $this->bukti());

        $this->assertSame('PJG-20261122-0001', $p->nomor_perpanjang);
        $this->assertSame(3, $p->tambahan_hari);
        $this->assertEquals(150000, (float) $p->biaya_perpanjangan);
        $this->assertSame('2026-11-24', $p->tanggal_selesai_lama->toDateString());
        $this->assertSame('2026-11-27', $sewa->fresh()->tanggal_selesai->toDateString());
        $this->assertEquals(300000, $sewa->fresh()->load('extensions')->totalDenganPerpanjangan());

        $bayar = $p->payments()->first();
        $this->assertEquals(150000, (float) $bayar->jumlah);
        $this->assertEquals($this->admin->id, $bayar->diunggah_oleh);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_perpanjangan_sewa_bulanan_dihitung_harian_dan_tidak_dikonversi(): void
    {
        $sewa = $this->sewa(['tipe_sewa' => 'BULANAN', 'jumlah_hari' => null]); // selesai 21 Desember

        $p = $this->layanan->perpanjang($this->admin, $sewa->id, CarbonImmutable::create(2026, 12, 23), $this->bukti());

        $this->assertSame(2, $p->tambahan_hari);
        $this->assertEquals(100000, (float) $p->biaya_perpanjangan);
        $this->assertSame(TipeSewa::BULANAN, $sewa->fresh()->tipe_sewa);
    }

    public function test_tanggal_baru_harus_setelah_tanggal_lama(): void
    {
        $sewa = $this->sewa(); // selesai 24 November

        foreach ([CarbonImmutable::create(2026, 11, 24), CarbonImmutable::create(2026, 11, 23)] as $tanggal) {
            $this->harusDitolak(PerpanjanganTidakValidException::class, fn () => $this->layanan->perpanjang($this->admin, $sewa->id, $tanggal, $this->bukti()));
        }

        $this->assertDatabaseCount('rental_extensions', 0);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_sewa_yang_sudah_berakhir_tidak_bisa_diperpanjang(): void
    {
        $sewa = $this->sewa();
        Rental::whereKey($sewa->id)->update(['status' => 'SELESAI', 'tanggal_mulai' => '2026-11-01', 'tanggal_selesai' => '2026-11-03']);

        $this->harusDitolak(PerpanjanganTidakValidException::class, fn () => $this->layanan->perpanjang(
            $this->admin, $sewa->id, CarbonImmutable::create(2026, 11, 10), $this->bukti()
        ));
    }

    public function test_perpanjangan_memakai_harga_harian_yang_tersimpan_di_sewa(): void
    {
        $sewa = $this->sewa();
        Garage::find('C001')->update(['harga_sewa_harian' => 99999]);

        $p = $this->layanan->perpanjang($this->admin, $sewa->id, CarbonImmutable::create(2026, 11, 25), $this->bukti());

        $this->assertEquals(50000, (float) $p->biaya_perpanjangan);
    }

    public function test_nomor_perpanjangan_berurutan_per_hari(): void
    {
        $sewa = $this->sewa();

        $a = $this->layanan->perpanjang($this->admin, $sewa->id, CarbonImmutable::create(2026, 11, 26), $this->bukti());
        $b = $this->layanan->perpanjang($this->admin, $sewa->id, CarbonImmutable::create(2026, 11, 28), $this->bukti());

        $this->assertSame('PJG-20261122-0001', $a->nomor_perpanjang);
        $this->assertSame('PJG-20261122-0002', $b->nomor_perpanjang);
        $this->assertSame('2026-11-28', $sewa->fresh()->tanggal_selesai->toDateString());
    }

    public function test_perpanjangan_melebihi_batas_pengaman_ditolak(): void
    {
        $sewa = $this->sewa();

        $this->harusDitolak(PerpanjanganTidakValidException::class, fn () => $this->layanan->perpanjang(
            $this->admin, $sewa->id, CarbonImmutable::create(2028, 1, 1), $this->bukti()
        ));
    }
}