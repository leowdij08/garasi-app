<?php

namespace App\Http\Requests;

use App\Services\PaymentProofStorage;
use Illuminate\Foundation\Http\FormRequest;

class StoreExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $lama = $this->route('rental')->tanggal_selesai->toDateString();

        return [
            'tanggal_selesai_baru' => ['required', 'date_format:Y-m-d', 'after:'.$lama],
            'bukti_bayar' => ['required', 'file', 'mimes:'.implode(',', PaymentProofStorage::TIPE_FILE), 'max:'.PaymentProofStorage::MAKS_KB],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_selesai_baru.required' => 'Tanggal selesai baru wajib diisi.',
            'tanggal_selesai_baru.date_format' => 'Format tanggal tidak valid.',
            'tanggal_selesai_baru.after' => 'Tanggal selesai baru harus setelah tanggal selesai lama.',
            'bukti_bayar.required' => 'Bukti pembayaran wajib diunggah.',
            'bukti_bayar.mimes' => 'Bukti pembayaran harus berupa gambar JPG, JPEG, atau PNG.',
            'bukti_bayar.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
            'bukti_bayar.uploaded' => 'Unggahan gagal. Pastikan ukuran gambar tidak lebih dari 5 MB.',
        ];
    }
}