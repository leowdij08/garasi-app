<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Penyewa</h2>
    </x-slot>

    @php($tenant = auth()->user()->tenant)

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 sm:rounded-lg p-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-900">
                Halo, {{ $tenant?->nama_lengkap }}.
            </div>

            @if ($tenant?->bolehMenyewa())
                <div class="bg-green-50 border border-green-200 text-green-800 sm:rounded-lg p-4 flex items-center justify-between">
                    <span>Akun Anda sudah diverifikasi. Anda dapat menyewa garasi.</span>
                    <a href="{{ route('penyewa.rentals.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Sewa Garasi Baru
                    </a>
                </div>
            @else
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 sm:rounded-lg p-4">
                    Akun Anda berstatus {{ $tenant?->status_verifikasi->value }}. Anda belum dapat menyewa garasi sampai admin menyetujui pendaftaran.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>