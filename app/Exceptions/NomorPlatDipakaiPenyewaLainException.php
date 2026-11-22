<?php

namespace App\Exceptions;

class NomorPlatDipakaiPenyewaLainException extends AturanSewaException
{
    public function __construct(string $plat)
    {
        parent::__construct("Nomor plat {$plat} sudah terdaftar atas penyewa lain.");
    }
}