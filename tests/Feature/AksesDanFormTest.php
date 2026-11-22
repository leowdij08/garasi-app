<?php

namespace Tests\Feature;

use App\Enums\StatusVerifikasi;
use App\Enums\TipeGarasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuatDataUji;
use Tests\TestCase;

class AksesDanFormTest extends TestCase
{
    use BuatDataUji, RefreshDatabase;

    /** Gambar PNG 1x1 piksel yang valid. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function formSewa(array $ganti = []): array
    {
        return array_merge([
            'tipe_kendaraan' => 'MOBIL',
            'nomor_plat' => 'd 1234 ab',
            'merek' => 'Toyota',
            'model' => 'Avanza',
            'tipe_sewa' => 'HARIAN',
            'jumlah_hari' => 3,
            'tipe_garasi' => 'MOBIL',
            'garage_id' => 'C001',
            'bukti_bayar' => UploadedFile::fake()->createWithContent('bukti.png', base64_decode(self::PNG)),
        ], $ganti);
    }

    private function dataDaftar(array $ganti = []): array
    {
        return array_merge([
            'nik' => '3171000000009999',
            'nama_lengkap' => 'Penyewa Baru',
            'nomor_telepon' => '081234567890',
            'email' => 'Baru@Uji.Test',
            'password' => 'RahasiaUji123',
            'password_confirmation' => 'RahasiaUji123',
        ], $ganti);
    }

    public function test_pendaftaran_membuat_penyewa_berstatus_pending(): void
    {
        $this->post('/register', $this->dataDaftar())->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'baru@uji.test', 'role' => 'PENYEWA']);
        $this->assertDatabaseHas('tenants', ['nik' => '3171000000009999', 'status_verifikasi' => 'PENDING']);
    }

    public function test_nik_dan_email_harus_unik(): void
    {
        $ada = $this->buatPenyewa();

        $this->post('/register', $this->dataDaftar(['nik' => $ada->nik, 'email' => strtoupper($ada->user->email)]))
            ->assertSessionHasErrors(['nik', 'email']);
    }

    public function test_nik_harus_16_digit_angka(): void
    {
        $this->post('/register', $this->dataDaftar(['nik' => '12345']))->assertSessionHasErrors('nik');
        $this->post('/register', $this->dataDaftar(['nik' => '31710000000099AB']))->assertSessionHasErrors('nik');
    }

    public function test_penyewa_tidak_boleh_membuka_halaman_admin(): void
    {
        $this->actingAs($this->buatPenyewa()->user)->get('/admin/garasi')->assertForbidden();
    }

    public function test_admin_tidak_boleh_membuka_halaman_penyewa(): void
    {
        $this->actingAs($this->buatAdmin())->get('/penyewa/sewa')->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin/garasi')->assertRedirect(route('login'));
    }

    public function test_penyewa_pending_tidak_dapat_membuka_form_sewa(): void
    {
        $pending = $this->buatPenyewa(StatusVerifikasi::PENDING);

        $this->actingAs($pending->user)->get(route('penyewa.rentals.create'))
            ->assertRedirect(route('penyewa.dashboard'));
    }

    public function test_penyewa_terverifikasi_dapat_membuka_form_sewa(): void
    {
        $this->actingAs($this->buatPenyewa()->user)->get(route('penyewa.rentals.create'))
            ->assertOk()
            ->assertSee('Sewa Garasi Baru');
    }

    public function test_admin_membuat_garasi_berkelompok_lewat_form(): void
    {
        $this->actingAs($this->buatAdmin())
            ->post(route('admin.garages.store'), ['tipe_garasi' => 'SEPEDA', 'jumlah' => 3, 'harga_sewa_harian' => 10000])
            ->assertRedirect(route('admin.garages.index'));

        $this->assertDatabaseCount('garages', 3);
        $this->assertDatabaseHas('garages', ['id' => 'S003']);
    }

    public function test_validasi_form_buat_garasi(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('admin.garages.store'), ['tipe_garasi' => 'LISTRIK', 'jumlah' => 1, 'harga_sewa_harian' => 80000])
            ->assertSessionHasErrors('harga_listrik_kwh');

        $this->actingAs($admin)
            ->post(route('admin.garages.store'), ['tipe_garasi' => 'SEPEDA', 'jumlah' => 1000, 'harga_sewa_harian' => 10000])
            ->assertSessionHasErrors('jumlah');

        $this->assertDatabaseCount('garages', 0);
    }

    public function test_penyewa_mengajukan_sewa_lewat_form(): void
    {
        Storage::fake('public');
        $this->travelTo(CarbonImmutable::create(2026, 11, 22, 10));
        $this->buatGarasi('C001', TipeGarasi::MOBIL, 50000);
        $penyewa = $this->buatPenyewa();

        $this->actingAs($penyewa->user)->post(route('penyewa.rentals.store'), $this->formSewa())
            ->assertRedirect(route('penyewa.rentals.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseHas('vehicles', ['nomor_plat' => 'D1234AB']);
        $this->assertDatabaseHas('garages', ['id' => 'C001', 'status' => 'USED']);
    }

    public function test_bukti_bayar_selain_gambar_ditolak(): void
    {
        Storage::fake('public');
        $this->buatGarasi('C001', TipeGarasi::MOBIL, 50000);
        $penyewa = $this->buatPenyewa();

        $this->actingAs($penyewa->user)->post(route('penyewa.rentals.store'), $this->formSewa([
            'bukti_bayar' => UploadedFile::fake()->createWithContent('bukti.pdf', "%PDF-1.4\n"),
        ]))->assertSessionHasErrors('bukti_bayar');

        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_penyewa_pending_tidak_dapat_menyimpan_sewa(): void
    {
        Storage::fake('public');
        $this->buatGarasi('C001', TipeGarasi::MOBIL, 50000);
        $pending = $this->buatPenyewa(StatusVerifikasi::PENDING);

        $this->actingAs($pending->user)->post(route('penyewa.rentals.store'), $this->formSewa())
            ->assertSessionHasErrors('sewa');

        $this->assertDatabaseCount('rentals', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
