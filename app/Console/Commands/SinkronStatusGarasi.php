<?php

namespace App\Console\Commands;

use App\Services\GarageStatusSynchronizer;
use Illuminate\Console\Command;

class SinkronStatusGarasi extends Command
{
    protected $signature = 'garasi:sinkron-status';

    protected $description = 'Menyelesaikan sewa yang sudah lewat tanggal selesai dan mengembalikan garasinya ke AVAILABLE';

    public function handle(GarageStatusSynchronizer $sinkron): int
    {
        [$sewa, $garasi] = $sinkron->sinkron();

        $this->info("Sinkronisasi selesai: {$sewa} sewa menjadi SELESAI, {$garasi} garasi kembali AVAILABLE.");

        return self::SUCCESS;
    }
}
