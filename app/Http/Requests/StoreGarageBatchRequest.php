<?php

namespace App\Http\Requests;

use App\Enums\TipeGarasi;
use App\Services\GarageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGarageBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'tipe_garasi' => ['required', Rule::enum(TipeGarasi::class)],
            'jumlah' => ['required', 'integer', 'min:1', 'max:'.GarageService::MAKS_PER_TIPE],
            'harga_sewa_harian' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'harga_listrik_kwh' => ['nullable', 'required_if:tipe_garasi,LISTRIK', 'numeric', 'gt:0', 'max:9999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipe_garasi.required' => 'Tipe garasi wajib dipilih.',
            'tipe_garasi.enum' => 'Tipe garasi tidak valid.',
            'jumlah.required' => 'Jumlah garasi wajib diisi.',
            'jumlah.integer' => 'Jumlah garasi harus berupa angka bulat.',
            'jumlah.min' => 'Jumlah garasi minimal 1.',
            'jumlah.max' => 'Jumlah garasi maksimal '.GarageService::MAKS_PER_TIPE.'.',
            'harga_sewa_harian.required' => 'Harga sewa harian wajib diisi.',
            'harga_sewa_harian.numeric' => 'Harga sewa harian harus berupa angka.',
            'harga_sewa_harian.gt' => 'Harga sewa harian harus lebih dari 0.',
            'harga_listrik_kwh.required_if' => 'Harga listrik per kWh wajib diisi untuk garasi mobil listrik.',
            'harga_listrik_kwh.numeric' => 'Harga listrik per kWh harus berupa angka.',
            'harga_listrik_kwh.gt' => 'Harga listrik per kWh harus lebih dari 0.',
        ];
    }
}