<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Penyewa</h2>
    </x-slot>

    @php($tenant = auth()->user()->tenant)

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-900">
                Halo, {{ $tenant?->nama_lengkap }}.
            </div>

            @if ($tenant?->bolehMenyewa())
                <div class="bg-green-50 border border-green-200 text-green-800 sm:rounded-lg p-4">
                    Akun Anda sudah diverifikasi. Anda dapat menyewa garasi.
                </div>
            @else
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 sm:rounded-lg p-4">
                    Akun Anda berstatus {{ $tenant?->status_verifikasi->value }}. Anda belum dapat menyewa garasi sampai admin menyetujui pendaftaran.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>