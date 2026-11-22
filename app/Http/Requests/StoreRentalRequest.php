<?php

namespace App\Http\Requests;

use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use App\Enums\TipeSewa;
use App\Services\PaymentProofStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tenant !== null;
    }

    /** Plat dinormalisasi (huruf besar, tanpa spasi) dan dikosongkan untuk sepeda. */
    protected function prepareForValidation(): void
    {
        $tipe = TipeKendaraan::tryFrom((string) $this->input('tipe_kendaraan'));
        $plat = null;

        if ($tipe === null || $tipe->wajibPlat()) {
            $bersih = strtoupper((string) preg_replace('/\s+/', '', (string) $this->input('nomor_plat')));
            $plat = $bersih === '' ? null : $bersih;
        }

        $this->merge(['nomor_plat' => $plat]);
    }

    public function rules(): array
    {
        $wajibPlat = fn () => TipeKendaraan::tryFrom((string) $this->input('tipe_kendaraan'))?->wajibPlat() ?? false;
        $mobilListrik = fn () => $this->input('tipe_kendaraan') === TipeKendaraan::MOBIL_LISTRIK->value;
        $harian = fn () => $this->input('tipe_sewa') === TipeSewa::HARIAN->value;
        $listrikDiGarasiListrik = fn () => $mobilListrik() && $this->input('tipe_garasi') === TipeGarasi::LISTRIK->value;

        return [
            'tipe_kendaraan' => ['required', Rule::enum(TipeKendaraan::class)],
            'nomor_plat' => [Rule::requiredIf($wajibPlat), 'nullable', 'regex:/^[A-Z]{1,2}[0-9]{1,4}[A-Z]{0,3}$/'],
            'merek' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:50'],
            'kapasitas_baterai_kwh' => [Rule::requiredIf($mobilListrik), 'nullable', 'numeric', 'gt:0', 'max:999.99'],
            'tipe_sewa' => ['required', Rule::enum(TipeSewa::class)],
            'jumlah_hari' => [Rule::requiredIf($harian), 'nullable', 'integer', 'min:1', 'max:366'],
            'tipe_garasi' => ['required', Rule::enum(TipeGarasi::class)],
            'garage_id' => ['required', 'string', 'exists:garages,id'],
            'sisa_baterai_kwh' => [Rule::requiredIf($listrikDiGarasiListrik), 'nullable', 'numeric', 'min:0'],
            'bukti_bayar' => ['required', 'file', 'mimes:'.implode(',', PaymentProofStorage::TIPE_FILE), 'max:'.PaymentProofStorage::MAKS_KB],
        ];
    }

    public function messages(): array
    {
        return [
            'tipe_kendaraan.required' => 'Tipe kendaraan wajib dipilih.',
            'nomor_plat.required' => 'Nomor plat wajib diisi untuk kendaraan ini.',
            'nomor_plat.regex' => 'Format nomor plat tidak valid. Contoh: D1234AB.',
            'merek.required' => 'Merek kendaraan wajib diisi.',
            'model.required' => 'Tipe/model kendaraan wajib diisi.',
            'kapasitas_baterai_kwh.required' => 'Kapasitas baterai wajib diisi untuk mobil listrik.',
            'kapasitas_baterai_kwh.numeric' => 'Kapasitas baterai harus berupa angka.',
            'kapasitas_baterai_kwh.gt' => 'Kapasitas baterai harus lebih dari 0.',
            'tipe_sewa.required' => 'Tipe sewa wajib dipilih.',
            'jumlah_hari.required' => 'Jumlah hari wajib diisi untuk sewa harian.',
            'jumlah_hari.integer' => 'Jumlah hari harus berupa angka bulat.',
            'jumlah_hari.min' => 'Jumlah hari minimal 1.',
            'jumlah_hari.max' => 'Jumlah hari terlalu besar.',
            'tipe_garasi.required' => 'Tipe garasi wajib dipilih.',
            'garage_id.required' => 'Garasi wajib dipilih.',
            'garage_id.exists' => 'Garasi yang dipilih tidak ditemukan.',
            'sisa_baterai_kwh.required' => 'Sisa baterai wajib diisi untuk mobil listrik di garasi mobil listrik.',
            'sisa_baterai_kwh.numeric' => 'Sisa baterai harus berupa angka.',
            'sisa_baterai_kwh.min' => 'Sisa baterai tidak boleh negatif.',
            'bukti_bayar.required' => 'Bukti pembayaran wajib diunggah.',
            'bukti_bayar.mimes' => 'Bukti pembayaran harus berupa gambar JPG, JPEG, atau PNG.',
            'bukti_bayar.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
            'bukti_bayar.uploaded' => 'Unggahan gagal. Pastikan ukuran gambar tidak lebih dari 5 MB.',
        ];
    }
}