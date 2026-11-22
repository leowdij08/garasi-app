<?php

namespace Tests\Feature;

use App\Enums\StatusVerifikasi;
use App\Enums\TipeGarasi;
use App\Services\RentalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuatDataUji;
use Tests\TestCase;

class TampilanDanBahasaTest extends TestCase
{
    use BuatDataUji, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_admin_menampilkan_ringkasan(): void
    {
        $this->buatGarasi('C001', TipeGarasi::MOBIL);
        $this->buatPenyewa(StatusVerifikasi::PENDING);

        $this->actingAs($this->buatAdmin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Pendaftar menunggu verifikasi');
    }

    public function test_dashboard_menghitung_garasi_yang_sedang_disewa(): void
    {
        Storage::fake('public');
        $this->buatGarasi('C001', TipeGarasi::MOBIL);
        $this->buatGarasi('C002', TipeGarasi::MOBIL);
        app(RentalService::class)->buat($this->buatPenyewa(), $this->dataSewa(), $this->bukti());

        $this->actingAs($this->buatAdmin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('kartuGarasi', function ($kartu) {
                $mobil = $kartu->first(fn ($k) => $k['tipe'] === TipeGarasi::MOBIL);

                return $mobil['total'] === 2 && $mobil['terpakai'] === 1 && $mobil['tersedia'] === 1;
            })
            ->assertViewHas('sewaAktif', 1);
    }

    public function test_login_gagal_menampilkan_pesan_bahasa_indonesia(): void
    {
        app()->setLocale('id');
        $penyewa = $this->buatPenyewa();

        $this->post('/login', ['email' => $penyewa->user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
    }
}
