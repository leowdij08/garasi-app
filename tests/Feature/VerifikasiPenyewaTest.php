<?php

namespace Tests\Feature;

use App\Enums\StatusVerifikasi;
use App\Exceptions\PendaftaranSudahDiputuskanException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\BuatDataUji;
use Tests\TestCase;

class VerifikasiPenyewaTest extends TestCase
{
    use BuatDataUji, RefreshDatabase;

    public function test_admin_dapat_menyetujui_pendaftar_pending(): void
    {
        $admin = $this->buatAdmin();
        $penyewa = $this->buatPenyewa(StatusVerifikasi::PENDING);
        $this->assertFalse($penyewa->bolehMenyewa());

        $penyewa->tentukanVerifikasi(StatusVerifikasi::APPROVED, $admin);

        $this->assertSame(StatusVerifikasi::APPROVED, $penyewa->status_verifikasi);
        $this->assertEquals($admin->id, $penyewa->diverifikasi_oleh);
        $this->assertNotNull($penyewa->diverifikasi_pada);
        $this->assertTrue($penyewa->bolehMenyewa());
    }

    public function test_admin_dapat_menolak_pendaftar_pending(): void
    {
        $penyewa = $this->buatPenyewa(StatusVerifikasi::PENDING);

        $penyewa->tentukanVerifikasi(StatusVerifikasi::REJECTED, $this->buatAdmin());

        $this->assertSame(StatusVerifikasi::REJECTED, $penyewa->status_verifikasi);
        $this->assertFalse($penyewa->bolehMenyewa());
    }

    public function test_keputusan_yang_sudah_ada_tidak_dapat_ditimpa(): void
    {
        $admin = $this->buatAdmin();
        $penyewa = $this->buatPenyewa(StatusVerifikasi::PENDING);
        $penyewa->tentukanVerifikasi(StatusVerifikasi::APPROVED, $admin);

        $this->harusDitolak(PendaftaranSudahDiputuskanException::class, fn () => $penyewa->tentukanVerifikasi(StatusVerifikasi::REJECTED, $admin));

        $this->assertSame(StatusVerifikasi::APPROVED, $penyewa->fresh()->status_verifikasi);
    }

    public function test_hasil_pending_bukan_keputusan_yang_valid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->buatPenyewa(StatusVerifikasi::PENDING)->tentukanVerifikasi(StatusVerifikasi::PENDING, $this->buatAdmin());
    }
}
