<?php

namespace App\Enums;

enum TipeGarasi: string
{
    case SEPEDA = 'SEPEDA';
    case MOBIL = 'MOBIL';
    case LISTRIK = 'LISTRIK';

    /** Huruf awal ID garasi: S001, C001, E001. */
    public function prefix(): string
    {
        return match ($this) {
            self::SEPEDA => 'S',
            self::MOBIL => 'C',
            self::LISTRIK => 'E',
        };
    }

    /** Nama yang ditampilkan di layar. */
    public function label(): string
    {
        return match ($this) {
            self::SEPEDA => 'Garasi Sepeda',
            self::MOBIL => 'Garasi Mobil',
            self::LISTRIK => 'Garasi Mobil Listrik',
        };
    }
}