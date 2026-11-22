<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Admin</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach ($kartuGarasi as $k)
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <div class="text-sm text-gray-500">{{ $k['tipe']->label() }}</div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ $k['tersedia'] }}
                            <span class="text-sm font-normal text-gray-500">tersedia dari {{ $k['total'] }}</span>
                        </div>
                        <div class="text-xs text-gray-500">Sedang disewa hari ini: {{ $k['terpakai'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('admin.tenants.index') }}" class="bg-white shadow-sm sm:rounded-lg p-4 hover:bg-gray-50">
                    <div class="text-sm text-gray-500">Pendaftar menunggu verifikasi</div>
                    <div class="text-2xl font-semibold {{ $menunggu > 0 ? 'text-yellow-600' : 'text-gray-900' }}">{{ $menunggu }}</div>
                </a>
                <a href="{{ route('admin.extensions.index') }}" class="bg-white shadow-sm sm:rounded-lg p-4 hover:bg-gray-50">
                    <div class="text-sm text-gray-500">Sewa aktif hari ini</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $sewaAktif }}</div>
                </a>
                <a href="{{ route('admin.reports.index') }}" class="bg-white shadow-sm sm:rounded-lg p-4 hover:bg-gray-50">
                    <div class="text-sm text-gray-500">Pendapatan {{ $namaBulan }}</div>
                    <div class="text-2xl font-semibold text-gray-900">Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}</div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>