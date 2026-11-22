<?php

namespace App\Exceptions;

class KendaraanSedangDisewaException extends AturanSewaException
{
    public function __construct(string $kendaraan)
    {
        parent::__construct("Kendaraan {$kendaraan} masih aktif disewa di garasi lain pada rentang tanggal tersebut.");
    }
}