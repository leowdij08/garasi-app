<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Garasi</h2>
            <a href="{{ route('admin.garages.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Buat Garasi
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 sm:rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach ($ringkasan as $r)
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <div class="text-sm text-gray-500">{{ $r['tipe']->label() }}</div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ $r['jumlah'] }}
                            <span class="text-sm font-normal text-gray-500">/ {{ \App\Services\GarageService::MAKS_PER_TIPE }}</span>
                        </div>
                        <div class="text-xs text-gray-500">Sisa kuota: {{ $r['sisa'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.garages.index') }}"
                   class="px-3 py-1 rounded-full border {{ $filter === null ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700 border-gray-300' }}">
                    Semua
                </a>
                @foreach (\App\Enums\TipeGarasi::cases() as $t)
                    <a href="{{ route('admin.garages.index', ['tipe' => $t->value]) }}"
                       class="px-3 py-1 rounded-full border {{ $filter === $t ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700 border-gray-300' }}">
                        {{ $t->label() }}
                    </a>
                @endforeach
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Harga Harian</th>
                            <th class="px-4 py-3">Harga Bulanan (25 x)</th>
                            <th class="px-4 py-3">Listrik / kWh</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-900">
                        @forelse ($daftar as $g)
                            <tr>
                                <td class="px-4 py-2 font-mono">{{ $g->id }}</td>
                                <td class="px-4 py-2">{{ $g->tipe_garasi->label() }}</td>
                                <td class="px-4 py-2">Rp {{ number_format($g->harga_sewa_harian, 0, ',', '.') }}</td>
                                <td class="px-4 py-2">Rp {{ number_format($g->harga_sewa_harian * 25, 0, ',', '.') }}</td>
                                <td class="px-4 py-2">
                                    {{ $g->harga_listrik_kwh !== null ? 'Rp '.number_format($g->harga_listrik_kwh, 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $g->status->value === 'AVAILABLE' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $g->status->value }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">Belum ada garasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $daftar->links() }}
        </div>
    </div>
</x-app-layout>