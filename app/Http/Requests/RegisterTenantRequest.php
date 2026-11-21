<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Email disimpan huruf kecil agar pengecekan unik tidak membedakan huruf besar-kecil (poin 30). */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'nik' => preg_replace('/\s+/', '', (string) $this->input('nik')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'digits:16', 'unique:tenants,nik'],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nomor_telepon' => ['required', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus berupa 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
            'nomor_telepon.regex' => 'Nomor telepon tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nik' => 'NIK',
            'nama_lengkap' => 'nama lengkap',
            'nomor_telepon' => 'nomor telepon',
            'email' => 'email',
            'password' => 'password',
        ];
    }
}