<?php

namespace App\Exceptions;

class PenyewaBelumDiverifikasiException extends AturanSewaException
{
    public function __construct()
    {
        parent::__construct('Akun Anda belum diverifikasi admin. Anda belum dapat menyewa garasi.');
    }
}