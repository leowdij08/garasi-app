<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Enums\StatusVerifikasi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Akun contoh untuk uji coba dan demo. Semua password: password */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@garasi.test'],
            ['password' => 'password', 'role' => RoleType::ADMIN]
        );

        $this->buatPenyewa('penyewa@garasi.test', '3171000000000001', 'Budi Santoso', '081200000001', StatusVerifikasi::APPROVED, $admin);
        $this->buatPenyewa('pending@garasi.test', '3171000000000002', 'Siti Aminah', '081200000002', StatusVerifikasi::PENDING, $admin);
    }

    private function buatPenyewa(string $email, string $nik, string $nama, string $telepon, StatusVerifikasi $status, User $admin): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['password' => 'password', 'role' => RoleType::PENYEWA]
        );

        $disetujui = $status === StatusVerifikasi::APPROVED;

        Tenant::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nik' => $nik,
                'nama_lengkap' => $nama,
                'nomor_telepon' => $telepon,
                'status_verifikasi' => $status,
                'diverifikasi_oleh' => $disetujui ? $admin->id : null,
                'diverifikasi_pada' => $disetujui ? now() : null,
            ]
        );
    }
}