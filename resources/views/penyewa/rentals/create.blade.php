<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sewa Garasi Baru</h2>
    </x-slot>

    <script>
        function sewaForm(cfg) {
            const old = cfg.old || {};
            const [Y, M, D] = cfg.today.split('-').map(Number);
            const dua = (n) => String(n).padStart(2, '0');

            return {
                kendaraanList: cfg.tipeKendaraan,
                garasiTipe: cfg.tipeGarasi,
                garasiData: cfg.garasi,
                faktor: cfg.faktor,
                tipeKendaraan: old.tipe_kendaraan || cfg.tipeKendaraan[0].value,
                plat: old.nomor_plat || '',
                merek: old.merek || '',
                model: old.model || '',
                kapasitas: old.kapasitas_baterai_kwh || '',
                tipeSewa: old.tipe_sewa || 'HARIAN',
                jumlahHari: old.jumlah_hari || 1,
                tipeGarasi: old.tipe_garasi || '',
                garageId: old.garage_id || '',
                sisa: old.sisa_baterai_kwh || '',
                notifikasi: '',

                init() {
                    this.sinkronTipeSewa();
                    this.$watch('tipeKendaraan', () => {
                        if (!this.tipeGarasiBoleh.some(t => t.value === this.tipeGarasi)) {
                            this.tipeGarasi = '';
                            this.garageId = '';
                        }
                    });
                    this.$watch('tipeGarasi', () => { this.garageId = ''; });
                    this.$watch('tipeSewa', () => this.sinkronTipeSewa());
                    this.$watch('jumlahHari', () => this.sinkronTipeSewa());
                },

                // Aturan yang sama dengan RentalTypeResolver di server (poin 4, 5, 19).
                sinkronTipeSewa() {
                    const hs = this.hariSebulan;
                    const n = parseInt(this.jumlahHari) || 0;
                    if (this.tipeSewa === 'HARIAN' && n >= hs) {
                        this.notifikasi = n > hs
                            ? `Sewa tidak dapat melebihi 1 bulan. Tipe sewa diubah menjadi Bulanan (${hs} hari).`
                            : `Sewa harian ${hs} hari sama dengan 1 bulan. Tipe sewa diubah menjadi Bulanan (${hs} hari).`;
                        this.tipeSewa = 'BULANAN';
                    } else if (this.tipeSewa === 'HARIAN') {
                        this.notifikasi = '';
                    }
                },

                get hariSebulan() { return new Date(Date.UTC(Y, M, 0)).getUTCDate(); },
                get kendaraan() { return this.kendaraanList.find(k => k.value === this.tipeKendaraan); },
                get tipeGarasiBoleh() { return this.garasiTipe.filter(t => this.kendaraan.garasi.includes(t.value)); },
                get daftarGarasi() { return this.garasiData[this.tipeGarasi] || []; },
                get garasiDipilih() { return this.daftarGarasi.find(g => g.id === this.garageId) || null; },
                get lamaHari() { return this.tipeSewa === 'BULANAN' ? this.hariSebulan : (parseInt(this.jumlahHari) || 0); },
                get tanggalMulai() { return this.format(new Date(Date.UTC(Y, M - 1, D))); },
                get tanggalSelesai() {
                    if (this.lamaHari < 1) return '-';
                    return this.format(new Date(Date.UTC(Y, M - 1, D + this.lamaHari - 1)));
                },
                get listrikBerlaku() { return this.tipeKendaraan === 'MOBIL_LISTRIK' && this.tipeGarasi === 'LISTRIK'; },
                get sisaTidakValid() {
                    const k = parseFloat(this.kapasitas), s = parseFloat(this.sisa);
                    return !(k > 0) || isNaN(s) || s < 0 || s > k;
                },
                get biayaSewa() {
                    if (!this.garasiDipilih) return 0;
                    const h = this.garasiDipilih.harian;
                    return this.tipeSewa === 'BULANAN' ? this.faktor * h : this.lamaHari * h;
                },
                get biayaListrik() {
                    if (!this.listrikBerlaku || !this.garasiDipilih || this.sisaTidakValid) return 0;
                    return (parseFloat(this.kapasitas) - parseFloat(this.sisa)) * (this.garasiDipilih.listrik || 0);
                },
                get total() { return this.biayaSewa + this.biayaListrik; },

                format(d) { return `${dua(d.getUTCDate())}/${dua(d.getUTCMonth() + 1)}/${d.getUTCFullYear()}`; },
                rp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); },
            };
        }
    </script>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @error('sewa')
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 sm:rounded-lg p-4">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('penyewa.rentals.store') }}" enctype="multipart/form-data"
                  x-data="sewaForm(@js($konfigurasi))" class="space-y-6">
                @csrf

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800">1. Detail Kendaraan</h3>

                    <div>
                        <x-input-label for="tipe_kendaraan" value="Tipe Kendaraan" />
                        <select id="tipe_kendaraan" name="tipe_kendaraan" x-model="tipeKendaraan"
                                class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <template x-for="k in kendaraanList" :key="k.value">
                                <option :value="k.value" x-text="k.label" :selected="k.value === tipeKendaraan"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('tipe_kendaraan')" class="mt-2" />
                    </div>

                    <div x-show="kendaraan.wajibPlat">
                        <x-input-label for="nomor_plat" value="Nomor Plat" />
                        <input id="nomor_plat" name="nomor_plat" type="text" x-model="plat" :disabled="!kendaraan.wajibPlat"
                               placeholder="Contoh: D 1234 AB" style="text-transform: uppercase"
                               class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" />
                        <x-input-error :messages="$errors->get('nomor_plat')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="merek" value="Merek" />
                            <input id="merek" name="merek" type="text" x-model="merek" required
                                   class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" />
                            <x-input-error :messages="$errors->get('merek')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="model" value="Tipe / Model" />
                            <input id="model" name="model" type="text" x-model="model" required
                                   class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" />
                            <x-input-error :messages="$errors->get('model')" class="mt-2" />
                        </div>
                    </div>

                    <div x-show="tipeKendaraan === 'MOBIL_LISTRIK'">
                        <x-input-label for="kapasitas_baterai_kwh" value="Kapasitas Baterai (kWh)" />
                        <input id="kapasitas_baterai_kwh" name="kapasitas_baterai_kwh" type="number" step="any" min="0"
                               x-model="kapasitas" :disabled="tipeKendaraan !== 'MOBIL_LISTRIK'"
                               class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" />
                        <x-input-error :messages="$errors->get('kapasitas_baterai_kwh')" class="mt-2" />
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800">2. Tipe dan Lama Sewa</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="tipe_sewa" value="Tipe Sewa" />
                            <select id="tipe_sewa" name="tipe_sewa" x-model="tipeSewa"
                                    class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="HARIAN">Harian</option>
                                <option value="BULANAN">Bulanan</option>
                            </select>
                            <x-input-error :messages="$errors->get('tipe_sewa')" class="mt-2" />
                        </div>
                        <div x-show="tipeSewa === 'HARIAN'">
                            <x-input-label for="jumlah_hari" value="Jumlah Hari" />
                            <input id="jumlah_hari" name="jumlah_hari" type="number" min="1" x-model="jumlahHari"
                                   :disabled="tipeSewa !== 'HARIAN'"
                                   class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" />
                            <x-input-error :messages="$errors->get('jumlah_hari')" class="mt-2" />
                        </div>
                    </div>

                    <div x-show="notifikasi !== ''" x-text="notifikasi"
                         class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-md p-3 text-sm"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">Tanggal Mulai (hari ini)</div>
                            <div class="font-semibold text-gray-900" x-text="tanggalMulai"></div>
                        </div>
                        <div>
                            <div class="text-gray-500">Tanggal Selesai (otomatis)</div>
                            <div class="font-semibold text-gray-900" x-text="tanggalSelesai"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800">3. Pilih Garasi</h3>

                    <div>
                        <x-input-label for="tipe_garasi" value="Tipe Garasi" />
                        <select id="tipe_garasi" name="tipe_garasi" x-model="tipeGarasi" required
                                class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">- pilih tipe garasi -</option>
                            <template x-for="t in tipeGarasiBoleh" :key="t.value">
                                <option :value="t.value" x-text="t.label" :selected="t.value === tipeGarasi"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('tipe_garasi')" class="mt-2" />
                    </div>

                    <div x-show="tipeGarasi !== ''">
                        <x-input-label for="garage_id" value="Garasi Tersedia Hari Ini" />
                        <select id="garage_id" name="garage_id" x-model="garageId" required
                                class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">- pilih garasi -</option>
                            <template x-for="g in daftarGarasi" :key="g.id">
                                <option :value="g.id" :selected="g.id === garageId"
                                        x-text="g.id + ' - ' + rp(g.harian) + ' / hari'"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">
                            <span x-text="daftarGarasi.length"></span> garasi tersedia.
                        </p>
                        <p class="mt-1 text-sm text-red-600" x-show="daftarGarasi.length === 0">
                            Tidak ada garasi tersedia untuk tipe ini hari ini.
                        </p>
                        <x-input-error :messages="$errors->get('garage_id')" class="mt-2" />
                    </div>

                    <div x-show="listrikBerlaku">
                        <x-input-label for="sisa_baterai_kwh" value="Sisa Baterai Saat Ini (kWh)" />
                        <input id="sisa_baterai_kwh" name="sisa_baterai_kwh" type="number" step="any" min="0"
                               x-model="sisa" :disabled="!listrikBerlaku"
                               class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" />
                        <p class="mt-1 text-sm text-red-600" x-show="listrikBerlaku && sisa !== '' && sisaTidakValid">
                            Sisa baterai harus di antara 0 dan kapasitas baterai.
                        </p>
                        <x-input-error :messages="$errors->get('sisa_baterai_kwh')" class="mt-2" />
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                    <h3 class="font-semibold text-gray-800">4. Total Biaya dan Pembayaran</h3>

                    <dl class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Lama sewa</dt>
                            <dd class="text-gray-900" x-text="lamaHari + ' hari'"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Biaya sewa</dt>
                            <dd class="text-gray-900" x-text="rp(biayaSewa)"></dd>
                        </div>
                        <div class="flex justify-between" x-show="listrikBerlaku">
                            <dt class="text-gray-600">Biaya isi listrik</dt>
                            <dd class="text-gray-900" x-text="rp(biayaListrik)"></dd>
                        </div>
                        <div class="flex justify-between border-t pt-2 text-base font-semibold">
                            <dt>Total yang harus dibayar</dt>
                            <dd x-text="rp(total)"></dd>
                        </div>
                    </dl>
                    <p class="text-xs text-gray-500">Total dihitung ulang oleh sistem saat disimpan.</p>

                    <div>
                        <x-input-label for="bukti_bayar" value="Bukti Pelunasan (gambar JPG/PNG, maks 5 MB)" />
                        <input id="bukti_bayar" name="bukti_bayar" type="file" accept=".jpg,.jpeg,.png" required
                               class="block mt-1 w-full text-sm text-gray-700" />
                        <x-input-error :messages="$errors->get('bukti_bayar')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('penyewa.dashboard') }}" class="underline text-sm text-gray-600 hover:text-gray-900">Batal</a>
                    <x-primary-button>Simpan Sewa</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>