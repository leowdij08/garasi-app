<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Verifikasi Penyewa</h2>
    </x-slot>

    @php
        $tab = ['PENDING' => 'Menunggu', 'APPROVED' => 'Disetujui', 'REJECTED' => 'Ditolak', 'SEMUA' => 'Semua'];
        $warna = [
            'PENDING' => 'bg-yellow-100 text-yellow-800',
            'APPROVED' => 'bg-green-100 text-green-800',
            'REJECTED' => 'bg-red-100 text-red-800',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 sm:rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            @error('verifikasi')
                <div class="bg-red-50 border border-red-200 text-red-800 sm:rounded-lg p-4">
                    {{ $message }}
                </div>
            @enderror

            <div class="flex flex-wrap gap-2 text-sm">
                @foreach ($tab as $kode => $nama)
                    <a href="{{ route('admin.tenants.index', ['status' => $kode]) }}"
                       class="px-3 py-1 rounded-full border {{ $filter === $kode ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700 border-gray-300' }}">
                        {{ $nama }}
                        @if ($kode !== 'SEMUA')
                            ({{ $jumlah[$kode] ?? 0 }})
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Tanggal Daftar</th>
                            <th class="px-4 py-3">NIK</th>
                            <th class="px-4 py-3">Nama Lengkap</th>
                            <th class="px-4 py-3">Telepon</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-900">
                        @forelse ($daftar as $t)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2 font-mono">{{ $t->nik }}</td>
                                <td class="px-4 py-2">{{ $t->nama_lengkap }}</td>
                                <td class="px-4 py-2">{{ $t->nomor_telepon }}</td>
                                <td class="px-4 py-2">{{ $t->user->email }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $warna[$t->status_verifikasi->value] }}">
                                        {{ $t->status_verifikasi->value }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    @if ($t->status_verifikasi === \App\Enums\StatusVerifikasi::PENDING)
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('admin.tenants.setujui', $t) }}"
                                                  onsubmit="return confirm('Setujui pendaftaran {{ e($t->nama_lengkap) }}? Pastikan KTP sudah divalidasi.')">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded bg-green-600 text-white text-xs font-semibold hover:bg-green-700">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.tenants.tolak', $t) }}"
                                                  onsubmit="return confirm('Tolak pendaftaran {{ e($t->nama_lengkap) }}?')">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded bg-red-600 text-white text-xs font-semibold hover:bg-red-700">Tolak</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500">
                                            Diputuskan {{ $t->diverifikasi_pada?->format('d/m/Y H:i') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">Tidak ada pendaftar pada kategori ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $daftar->links() }}
        </div>
    </div>
</x-app-layout>