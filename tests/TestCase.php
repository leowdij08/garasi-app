<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public const DATABASE_UJI = 'garasi_test';

    /**
     * Pengaman: RefreshDatabase menghapus semua tabel, jadi tes hanya boleh berjalan di garasi_test.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $koneksi = $app['config']->get('database.default');
        $nama = $app['config']->get("database.connections.{$koneksi}.database");

        if ($nama !== self::DATABASE_UJI) {
            throw new RuntimeException(
                "Tes dihentikan: database yang dipakai adalah '{$nama}', seharusnya '".self::DATABASE_UJI."'. "
                .'Periksa pengaturan DB_CONNECTION dan DB_DATABASE di phpunit.xml.'
            );
        }

        return $app;
    }
}