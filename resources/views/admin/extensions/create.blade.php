<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Perpanjang Sewa {{ $rental->garage_id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @error('perpanjang')
                <div class="bg-red-50 border border-red-200 text-red-800 sm:rounded-lg p-4">{{ $message }}</div>
            @enderror

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                    <div><dt class="text-gray-500">Nomor Nota</dt><dd class="font-mono">{{ $rental->nomor_nota }}</dd></div>
                    <div><dt class="text-gray-500">Penyewa</dt><dd>{{ $rental->tenant->nama_lengkap }}</dd></div>
                    <div><dt class="text-gray-500">Kendaraan</dt><dd>{{ $rental->vehicle->nomor_plat ?? $rental->vehicle->tipe_kendaraan->label() }} - {{ $rental->vehicle->merek }} {{ $rental->vehicle->model }}</dd></div>
                    <div><dt class="text-gray-500">Tipe Sewa</dt><dd>{{ $rental->tipe_sewa->value }}</dd></div>
                    <div><dt class="text-gray-500">Tanggal Mulai</dt><dd>{{ $rental->tanggal_mulai->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gray-500">Tanggal Selesai Saat Ini</dt><dd class="font-semibold">{{ $rental->tanggal_selesai->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gray-500">Harga Sewa Harian</dt><dd>Rp {{ number_format($rental->harga_sewa_harian, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-gray-500">Total Sewa + Perpanjangan</dt><dd>Rp {{ number_format($rental->totalDenganPerpanjangan(), 0, ',', '.') }}</dd></div>
                </dl>
            </div>

            <form method="POST" action="{{ route('admin.extensions.store', $rental) }}" enctype="multipart/form-data"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4"
                  x-data="{
                      lama: @js($rental->tanggal_selesai->toDateString()),
                      baru: @js(old('tanggal_selesai_baru', '')),
                      harga: @js((float) $rental->harga_sewa_harian),
                      get tambahan() {
                          if (!this.baru) return 0;
                          const a = Date.parse(this.lama + 'T00:00:00Z');
                          const b = Date.parse(this.baru + 'T00:00:00Z');
                          return isNaN(b) ? 0 : Math.round((b - a) / 86400000);
                      },
                      get biaya() { return this.tambahan > 0 ? this.tambahan * this.harga : 0; },
                      rp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); },
                  }">
                @csrf

                <div>
                    <x-input-label for="tanggal_selesai_baru" value="Tanggal Selesai Baru" />
                    <input id="tanggal_selesai_baru" name="tanggal_selesai_baru" type="date" required
                           x-model="baru" min="{{ $rental->tanggal_selesai->copy()->addDay()->toDateString() }}"
                           class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" />
                    <x-input-error :messages="$errors->get('tanggal_selesai_baru')" class="mt-2" />
                </div>

                <dl class="text-sm space-y-1">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Tambahan hari</dt>
                        <dd x-text="tambahan > 0 ? tambahan + ' hari' : '-'"></dd>
                    </div>
                    <div class="flex justify-between border-t pt-2 text-base font-semibold">
                        <dt>Sisa biaya yang harus dibayar</dt>
                        <dd x-text="rp(biaya)"></dd>
                    </div>
                </dl>
                <p class="text-xs text-gray-500">Perpanjangan dihitung harian (harga harian x tambahan hari) dan dihitung ulang oleh sistem saat disimpan.</p>

                <div>
                    <x-input-label for="bukti_bayar" value="Bukti Bayar (gambar JPG/PNG, maks 5 MB)" />
                    <input id="bukti_bayar" name="bukti_bayar" type="file" accept=".jpg,.jpeg,.png" required
                           class="block mt-1 w-full text-sm text-gray-700" />
                    <x-input-error :messages="$errors->get('bukti_bayar')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('admin.extensions.index') }}" class="underline text-sm text-gray-600 hover:text-gray-900">Batal</a>
                    <x-primary-button>Simpan Perpanjangan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>