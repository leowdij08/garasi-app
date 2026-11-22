<?php

namespace Tests\Support;

use App\Enums\RoleType;
use App\Enums\StatusGarasi;
use App\Enums\StatusVerifikasi;
use App\Enums\TipeGarasi;
use App\Models\Garage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Throwable;

/** Pembantu untuk menyiapkan data uji. */
trait BuatDataUji
{
    private int $urutanData = 0;

    protected function buatAdmin(): User
    {
        $n = ++$this->urutanData;

        return User::create(['email' => "admin{$n}@uji.test", 'password' => 'password', 'role' => RoleType::ADMIN]);
    }

    protected function buatPenyewa(StatusVerifikasi $status = StatusVerifikasi::APPROVED): Tenant
    {
        $n = ++$this->urutanData;
        $user = User::create(['email' => "penyewa{$n}@uji.test", 'password' => 'password', 'role' => RoleType::PENYEWA]);

        return Tenant::create([
            'user_id' => $user->id,
            'nik' => '31710000'.str_pad((string) $n, 8, '0', STR_PAD_LEFT),
            'nama_lengkap' => "Penyewa {$n}",
            'nomor_telepon' => '0812000000'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'status_verifikasi' => $status,
        ]);
    }

    protected function buatGarasi(string $id, TipeGarasi $tipe, float $harian = 50000, ?float $listrik = null): Garage
    {
        return Garage::create([
            'id' => $id,
            'tipe_garasi' => $tipe,
            'harga_sewa_harian' => $harian,
            'harga_listrik_kwh' => $listrik,
            'status' => StatusGarasi::AVAILABLE,
        ]);
    }

    /** Isian Sewa Baru yang valid: mobil bensin, harian 3 hari, garasi C001. */
    protected function dataSewa(array $ganti = []): array
    {
        return array_merge([
            'tipe_kendaraan' => 'MOBIL',
            'nomor_plat' => 'D1234AB',
            'merek' => 'Toyota',
            'model' => 'Avanza',
            'kapasitas_baterai_kwh' => null,
            'tipe_sewa' => 'HARIAN',
            'jumlah_hari' => 3,
            'tipe_garasi' => 'MOBIL',
            'garage_id' => 'C001',
            'sisa_baterai_kwh' => null,
        ], $ganti);
    }

    protected function bukti(): UploadedFile
    {
        return UploadedFile::fake()->create('bukti.png', 10, 'image/png');
    }

    /** Memastikan aksi ditolak dengan jenis galat tertentu. */
    protected function harusDitolak(string $kelas, callable $aksi): void
    {
        try {
            $aksi();
        } catch (Throwable $e) {
            $this->assertInstanceOf($kelas, $e, 'Jenis galat tidak sesuai: '.$e->getMessage());

            return;
        }

        $this->fail("Seharusnya melempar {$kelas}, tetapi tidak ada galat.");
    }
}
