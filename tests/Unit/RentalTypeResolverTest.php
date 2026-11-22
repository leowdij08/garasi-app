<?php

namespace Tests\Unit;

use App\Enums\TipeSewa;
use App\Services\RentalTypeResolver;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RentalTypeResolverTest extends TestCase
{
    private RentalTypeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new RentalTypeResolver();
    }

    public function test_hari_sebulan_mengikuti_bulan_dan_tahun_kabisat(): void
    {
        $this->assertSame(28, $this->resolver->hariSebulan(CarbonImmutable::create(2026, 2, 15)));
        $this->assertSame(29, $this->resolver->hariSebulan(CarbonImmutable::create(2028, 2, 1)));
        $this->assertSame(30, $this->resolver->hariSebulan(CarbonImmutable::create(2026, 11, 22)));
        $this->assertSame(31, $this->resolver->hariSebulan(CarbonImmutable::create(2026, 12, 20)));
    }

    public function test_harian_kurang_dari_hari_sebulan_tetap_harian(): void
    {
        $hasil = $this->resolver->resolve(CarbonImmutable::create(2026, 2, 1), TipeSewa::HARIAN, 27);

        $this->assertSame(TipeSewa::HARIAN, $hasil->tipeSewa);
        $this->assertSame(27, $hasil->jumlahHari);
        $this->assertSame(27, $hasil->lamaHari);
        $this->assertSame('2026-02-27', $hasil->tanggalSelesai->toDateString());
        $this->assertNull($hasil->notifikasi);
    }

    public function test_harian_sama_dengan_hari_sebulan_menjadi_bulanan(): void
    {
        $hasil = $this->resolver->resolve(CarbonImmutable::create(2026, 2, 1), TipeSewa::HARIAN, 28);

        $this->assertSame(TipeSewa::BULANAN, $hasil->tipeSewa);
        $this->assertSame(0, $hasil->jumlahHari);
        $this->assertSame(28, $hasil->lamaHari);
        $this->assertSame('2026-02-28', $hasil->tanggalSelesai->toDateString());
        $this->assertTrue($hasil->diubahOtomatis());
    }

    public function test_harian_melebihi_hari_sebulan_menjadi_bulanan_dengan_notifikasi(): void
    {
        $hasil = $this->resolver->resolve(CarbonImmutable::create(2026, 2, 1), TipeSewa::HARIAN, 35);

        $this->assertSame(TipeSewa::BULANAN, $hasil->tipeSewa);
        $this->assertSame(28, $hasil->lamaHari);
        $this->assertSame(
            'Sewa tidak dapat melebihi 1 bulan. Tipe sewa diubah menjadi Bulanan (28 hari).',
            $hasil->notifikasi
        );
    }

    public function test_mulai_20_desember_harian_30_hari_masih_harian(): void
    {
        $hasil = $this->resolver->resolve(CarbonImmutable::create(2026, 12, 20), TipeSewa::HARIAN, 30);

        $this->assertSame(TipeSewa::HARIAN, $hasil->tipeSewa);
        $this->assertSame('2027-01-18', $hasil->tanggalSelesai->toDateString());
    }

    public function test_mulai_20_desember_bulanan_dihitung_31_hari(): void
    {
        $hasil = $this->resolver->resolve(CarbonImmutable::create(2026, 12, 20), TipeSewa::BULANAN, 0);

        $this->assertSame(31, $hasil->lamaHari);
        $this->assertSame('2027-01-19', $hasil->tanggalSelesai->toDateString());
    }

    public function test_bulanan_mulai_1_februari_2026_selesai_28_februari(): void
    {
        $hasil = $this->resolver->resolve(CarbonImmutable::create(2026, 2, 1), TipeSewa::BULANAN, 0);

        $this->assertSame(28, $hasil->lamaHari);
        $this->assertSame('2026-02-28', $hasil->tanggalSelesai->toDateString());
        $this->assertNull($hasil->notifikasi);
    }

    public function test_februari_kabisat_2028_batas_harian_adalah_28_hari(): void
    {
        $harian = $this->resolver->resolve(CarbonImmutable::create(2028, 2, 1), TipeSewa::HARIAN, 28);
        $bulanan = $this->resolver->resolve(CarbonImmutable::create(2028, 2, 1), TipeSewa::HARIAN, 29);

        $this->assertSame(TipeSewa::HARIAN, $harian->tipeSewa);
        $this->assertSame(TipeSewa::BULANAN, $bulanan->tipeSewa);
        $this->assertSame(29, $bulanan->lamaHari);
    }

    public function test_input_bulanan_mengabaikan_jumlah_hari(): void
    {
        $hasil = $this->resolver->resolve(CarbonImmutable::create(2026, 11, 22), TipeSewa::BULANAN, 5);

        $this->assertSame(30, $hasil->lamaHari);
        $this->assertSame(0, $hasil->jumlahHari);
    }

    public function test_harian_nol_hari_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resolver->resolve(CarbonImmutable::create(2026, 11, 22), TipeSewa::HARIAN, 0);
    }
}