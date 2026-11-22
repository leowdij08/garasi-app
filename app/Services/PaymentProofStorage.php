<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Penyimpanan bukti bayar: JPG, JPEG, atau PNG, maksimal 5 MB (poin 28). */
class PaymentProofStorage
{
    public const TIPE_FILE = ['jpg', 'jpeg', 'png'];

    public const MAKS_KB = 5120;

    public const DISK = 'public';

    public const FOLDER = 'bukti-bayar';

    /** Mengembalikan path relatif di disk public. */
    public function simpan(UploadedFile $file): string
    {
        return $file->store(self::FOLDER, self::DISK);
    }

    public function hapus(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}