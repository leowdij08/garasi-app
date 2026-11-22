<?php

namespace App\Exceptions;

use RuntimeException;

class PendaftaranSudahDiputuskanException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Pendaftaran ini sudah diputuskan sebelumnya. Muat ulang halaman untuk melihat status terbaru.');
    }
}