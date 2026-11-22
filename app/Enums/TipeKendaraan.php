<?php

namespace App\Enums;

enum TipeKendaraan: string
{
    case SEPEDA = 'SEPEDA';
    case SEPEDA_MOTOR = 'SEPEDA_MOTOR';
    case MOBIL = 'MOBIL';
    case MOBIL_LISTRIK = 'MOBIL_LISTRIK';

    public function label(): string
    {
        return match ($this) {
            self::SEPEDA => 'Sepeda',
            self::SEPEDA_MOTOR => 'Sepeda Motor',
            self::MOBIL => 'Mobil',
            self::MOBIL_LISTRIK => 'Mobil Listrik',
        };
    }

    /** Nomor plat wajib untuk semua kendaraan kecuali sepeda (poin 12). */
    public function wajibPlat(): bool
    {
        return $this !== self::SEPEDA;
    }

    /**
     * Tipe garasi yang boleh dipilih untuk kendaraan ini (poin 11).
     *
     * @return list<TipeGarasi>
     */
    public function tipeGarasiDiizinkan(): array
    {
        return match ($this) {
            self::SEPEDA, self::SEPEDA_MOTOR => [TipeGarasi::SEPEDA],
            self::MOBIL => [TipeGarasi::MOBIL],
            self::MOBIL_LISTRIK => [TipeGarasi::MOBIL, TipeGarasi::LISTRIK],
        };
    }

    public function bolehDiGarasi(TipeGarasi $garasi): bool
    {
        return in_array($garasi, $this->tipeGarasiDiizinkan(), true);
    }
}
