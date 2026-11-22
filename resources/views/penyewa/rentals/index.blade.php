<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sewa Saya</h2>
            <a href="{{ route('penyewa.rentals.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Sewa Baru
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 sm:rounded-lg p-4">{{ session('status') }}</div>
            @endif
            @if (session('info'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 sm:rounded-lg p-4">{{ session('info') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Nomor Nota</th>
                            <th class="px-4 py-3">Garasi</th>
                            <th class="px-4 py-3">Kendaraan</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Mulai</th>
                            <th class="px-4 py-3">Selesai</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-900">
                        @forelse ($daftar as $r)
                            <tr>
                                <td class="px-4 py-2 font-mono whitespace-nowrap">{{ $r->nomor_nota }}</td>
                                <td class="px-4 py-2 font-mono">{{ $r->garage_id }}</td>
                                <td class="px-4 py-2">
                                    {{ $r->vehicle->nomor_plat ?? $r->vehicle->tipe_kendaraan->label() }}
                                    <div class="text-xs text-gray-500">{{ $r->vehicle->merek }} {{ $r->vehicle->model }}</div>
                                </td>
                                <td class="px-4 py-2">{{ $r->tipe_sewa->value }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $r->tanggal_mulai->format('d/m/Y') }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $r->tanggal_selesai->format('d/m/Y') }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $r->status->value === 'AKTIF' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $r->status->value }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    @if ($bukti = $r->payments->first())
                                        <a class="underline text-indigo-600" target="_blank" href="{{ asset('storage/'.$bukti->bukti_gambar) }}">Lihat</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-gray-500">Belum ada sewa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $daftar->links() }}
        </div>
    </div>
</x-app-layout>