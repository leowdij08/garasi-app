<?php

namespace App\Http\Middleware;

use App\Services\GarageStatusSynchronizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Menjalankan sinkronisasi status sebelum halaman yang menampilkan garasi atau perpanjangan dibuka. */
class SinkronStatusGarasi
{
    public function __construct(private readonly GarageStatusSynchronizer $sinkron) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->sinkron->sinkron();

        return $next($request);
    }
}
