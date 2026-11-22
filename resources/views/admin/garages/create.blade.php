<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Garasi</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6"
                 x-data="{ tipe: @js(old('tipe_garasi', 'SEPEDA')), sisa: @js($sisa) }">
                <form method="POST" action="{{ route('admin.garages.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="tipe_garasi" value="Tipe Garasi" />
                        <select id="tipe_garasi" name="tipe_garasi" x-model="tipe"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach (\App\Enums\TipeGarasi::cases() as $t)
                                <option value="{{ $t->value }}">{{ $t->label() }} ({{ $t->prefix() }}001 - {{ $t->prefix() }}999)</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tipe_garasi')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="jumlah" value="Jumlah Garasi" />
                        <x-text-input id="jumlah" class="block mt-1 w-full" type="number" name="jumlah" :value="old('jumlah')" min="1" required />
                        <p class="mt-1 text-sm text-gray-500">
                            Sisa kuota tipe ini: <span class="font-semibold" x-text="sisa[tipe]"></span> garasi.
                        </p>
                        <x-input-error :messages="$errors->get('jumlah')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="harga_sewa_harian" value="Harga Sewa Harian (Rp)" />
                        <x-text-input id="harga_sewa_harian" class="block mt-1 w-full" type="number" step="any" name="harga_sewa_harian" :value="old('harga_sewa_harian')" min="0" required />
                        <p class="mt-1 text-sm text-gray-500">Harga bulanan otomatis 25 x harga harian.</p>
                        <x-input-error :messages="$errors->get('harga_sewa_harian')" class="mt-2" />
                    </div>

                    <div class="mt-4" x-show="tipe === 'LISTRIK'">
                        <x-input-label for="harga_listrik_kwh" value="Harga Isi Listrik per kWh (Rp)" />
                        <x-text-input id="harga_listrik_kwh" class="block mt-1 w-full" type="number" step="any" name="harga_listrik_kwh" :value="old('harga_listrik_kwh')" min="0" />
                        <x-input-error :messages="$errors->get('harga_listrik_kwh')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route('admin.garages.index') }}" class="underline text-sm text-gray-600 hover:text-gray-900">Batal</a>
                        <x-primary-button>Simpan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>