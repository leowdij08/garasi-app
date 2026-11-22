<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Perpanjang Sewa</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 sm:rounded-lg p-4">{{ session('status') }}</div>
            @endif
            @error('perpanjang')
                <div class="bg-red-50 border border-red-200 text-red-800 sm:rounded-lg p-4">{{ $message }}</div>
            @enderror

            <p class="text-sm text-gray-600">Garasi yang sedang disewa pada hari ini ({{ now()->format('d/m/Y') }}).</p>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Garasi</th>
                            <th class="px-4 py-3">Penyewa</th>
                            <th class="px-4 py-3">Kendaraan</th>
                            <th class="px-4 py-3">Nomor Nota</th>
                            <th class="px-4 py-3">Mulai</th>
                            <th class="px-4 py-3">Selesai</th>
                            <th class="px-4 py-3">Diperpanjang</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-900">
                        @forelse ($daftar as $r)
                            <tr>
                                <td class="px-4 py-2 font-mono">{{ $r->garage_id }}</td>
                                <td class="px-4 py-2">{{ $r->tenant->nama_lengkap }}</td>
                                <td class="px-4 py-2">
                                    {{ $r->vehicle->nomor_plat ?? $r->vehicle->tipe_kendaraan->label() }}
                                    <div class="text-xs text-gray-500">{{ $r->vehicle->merek }} {{ $r->vehicle->model }}</div>
                                </td>
                                <td class="px-4 py-2 font-mono whitespace-nowrap">{{ $r->nomor_nota }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $r->tanggal_mulai->format('d/m/Y') }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $r->tanggal_selesai->format('d/m/Y') }}</td>
                                <td class="px-4 py-2">{{ $r->extensions->count() }}x</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('admin.extensions.create', $r) }}"
                                       class="px-3 py-1 rounded bg-gray-800 text-white text-xs font-semibold hover:bg-gray-700">Perpanjang</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500">Tidak ada garasi yang sedang disewa hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $daftar->links() }}
        </div>
    </div>
</x-app-layout>