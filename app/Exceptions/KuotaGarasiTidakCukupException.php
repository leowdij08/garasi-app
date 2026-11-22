<?php

namespace App\Exceptions;

use App\Enums\TipeGarasi;
use RuntimeException;

class KuotaGarasiTidakCukupException extends RuntimeException
{
    public function __construct(public readonly TipeGarasi $tipe, public readonly int $sisa)
    {
        parent::__construct(
            "Sisa kuota {$tipe->label()} tinggal {$sisa}. Jumlah yang diminta melebihi sisa kuota."
        );
    }
}
