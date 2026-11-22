<?php

namespace Tests\Unit;

use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use PHPUnit\Framework\TestCase;

class TipeKendaraanTest extends TestCase
{
    public function test_sepeda_dan_motor_hanya_boleh_garasi_sepeda(): void
    {
        foreach ([TipeKendaraan::SEPEDA, TipeKendaraan::SEPEDA_MOTOR] as $tipe) {
            $this->assertTrue($tipe->bolehDiGarasi(TipeGarasi::SEPEDA));
            $this->assertFalse($tipe->bolehDiGarasi(TipeGarasi::MOBIL));
            $this->assertFalse($tipe->bolehDiGarasi(TipeGarasi::LISTRIK));
        }
    }

    public function test_mobil_bensin_hanya_boleh_garasi_mobil(): void
    {
        $this->assertTrue(TipeKendaraan::MOBIL->bolehDiGarasi(TipeGarasi::MOBIL));
        $this->assertFalse(TipeKendaraan::MOBIL->bolehDiGarasi(TipeGarasi::SEPEDA));
        $this->assertFalse(TipeKendaraan::MOBIL->bolehDiGarasi(TipeGarasi::LISTRIK));
    }

    public function test_mobil_listrik_boleh_garasi_mobil_atau_garasi_listrik(): void
    {
        $this->assertTrue(TipeKendaraan::MOBIL_LISTRIK->bolehDiGarasi(TipeGarasi::MOBIL));
        $this->assertTrue(TipeKendaraan::MOBIL_LISTRIK->bolehDiGarasi(TipeGarasi::LISTRIK));
        $this->assertFalse(TipeKendaraan::MOBIL_LISTRIK->bolehDiGarasi(TipeGarasi::SEPEDA));
    }

    public function test_nomor_plat_wajib_kecuali_sepeda(): void
    {
        $this->assertFalse(TipeKendaraan::SEPEDA->wajibPlat());
        $this->assertTrue(TipeKendaraan::SEPEDA_MOTOR->wajibPlat());
        $this->assertTrue(TipeKendaraan::MOBIL->wajibPlat());
        $this->assertTrue(TipeKendaraan::MOBIL_LISTRIK->wajibPlat());
    }
}