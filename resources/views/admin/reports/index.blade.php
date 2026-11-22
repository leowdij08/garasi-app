<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan Sewa Garasi</h2>
    </x-slot>

    @php
        $tab = ['bulan' => 'Per Bulan', 'hari' => 'Per Hari', 'tipe' => 'Per Tipe Garasi', 'penyewa' => 'Per Penyewa'];
        $kolomPertama = ['bulan' => 'Bulan', 'hari' => 'Tanggal Mulai', 'tipe' => 'Tipe Garasi', 'penyewa' => 'Penyewa'];
        $namaBulan = \App\Services\ReportService::BULAN;
        $wajibPeriode = in_array($jenis, ['bulan', 'hari'], true);
        $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap gap-2 text-sm">
                @foreach ($tab as $kode => $nama)
                    <a href="{{ route('admin.reports.index', ['jenis' => $kode]) }}"
                       class="px-3 py-1 rounded-full border {{ $jenis === $kode ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700 border-gray-300' }}">
                        {{ $nama }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.reports.index') }}" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-end gap-4">
                <input type="hidden" name="jenis" value="{{ $jenis }}">

                <div>
                    <x-input-label for="tahun" value="Tahun" />
                    <input id="tahun" name="tahun" type="number" min="2000" max="2100" value="{{ $tahun }}"
                           placeholder="Semua" @if ($wajibPeriode) required @endif
                           class="block mt-1 w-32 border-gray-300 rounded-md shadow-sm" />
                </div>

                @if ($jenis !== 'bulan')
                    <div>
                        <x-input-label for="bulan" value="Bulan" />
                        <select id="bulan" name="bulan" class="block mt-1 border-gray-300 rounded-md shadow-sm">
                            @if ($jenis !== 'hari')
                                <option value="">Semua</option>
                            @endif
                            @foreach ($namaBulan as $no => $nama)
                                <option value="{{ $no }}" @selected($bulan === $no)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <x-primary-button>Tampilkan</x-primary-button>
            </form>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 sm:rounded-lg p-4 text-sm">
                    @foreach ($errors->all() as $pesan)
                        <div>{{ $pesan }}</div>
                    @endforeach
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <div class="px-4 py-3 border-b text-sm text-gray-700">
                    <span class="font-semibold">{{ $tab[$jenis] }}</span> - {{ $periode }}
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">{{ $kolomPertama[$jenis] }}</th>
                            <th class="px-4 py-3 text-right">Jumlah Sewa</th>
                            <th class="px-4 py-3 text-right">Biaya Sewa</th>
                            <th class="px-4 py-3 text-right">Biaya Perpanjangan</th>
                            <th class="px-4 py-3 text-right">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-900">
                        @forelse ($hasil['baris'] as $b)
                            <tr class="{{ $b['jumlah'] === 0 ? 'text-gray-400' : '' }}">
                                <td class="px-4 py-2">
                                    {{ $b['label'] }}
                                    @if ($b['sub'])
                                        <div class="text-xs text-gray-500">{{ $b['sub'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right">{{ $b['jumlah'] }}</td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">{{ $rp($b['sewa']) }}</td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">{{ $rp($b['perpanjangan']) }}</td>
                                <td class="px-4 py-2 text-right whitespace-nowrap font-medium">{{ $rp($b['pendapatan']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">Tidak ada sewa pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 font-semibold text-gray-900">
                        <tr>
                            <td class="px-4 py-3">Total</td>
                            <td class="px-4 py-3 text-right">{{ $hasil['total']['jumlah'] }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">{{ $rp($hasil['total']['sewa']) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">{{ $rp($hasil['total']['perpanjangan']) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">{{ $rp($hasil['total']['pendapatan']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="text-xs text-gray-500">
                Sewa dikelompokkan menurut tanggal mulai. Biaya sewa sudah termasuk biaya isi listrik.
                Pendapatan = total biaya sewa + biaya perpanjangan.
            </p>
        </div>
    </div>
</x-app-layout>