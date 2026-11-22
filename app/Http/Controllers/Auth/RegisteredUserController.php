<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleType;
use App\Enums\StatusVerifikasi;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterTenantRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pendaftaran akun penyewa; status awal PENDING sampai diverifikasi admin.
 */
class RegisteredUserController extends Controller
{
    /** Menampilkan form pendaftaran penyewa. */
    public function create(): View
    {
        return view('auth.register');
    }

    /** Menyimpan akun dan data penyewa dalam satu transaksi; status awal PENDING. */
    public function store(RegisterTenantRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'role' => RoleType::PENYEWA,
            ]);

            Tenant::create([
                'user_id' => $user->id,
                'nik' => $request->input('nik'),
                'nama_lengkap' => $request->input('nama_lengkap'),
                'nomor_telepon' => $request->input('nomor_telepon'),
                'status_verifikasi' => StatusVerifikasi::PENDING,
            ]);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
