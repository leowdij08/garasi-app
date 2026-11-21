<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_extensions', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_perpanjang', 25)->unique();
            $table->foreignId('rental_id')->constrained('rentals');
            $table->foreignId('admin_id')->constrained('users');
            $table->date('tanggal_selesai_lama');
            $table->date('tanggal_selesai_baru');
            $table->unsignedSmallInteger('tambahan_hari');
            $table->decimal('biaya_perpanjangan', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_extensions');
    }
};
