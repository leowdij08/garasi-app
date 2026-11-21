<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_nota', 25)->unique();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('garage_id', 4);
            $table->foreign('garage_id')->references('id')->on('garages');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->string('tipe_sewa', 10);
            $table->unsignedSmallInteger('jumlah_hari')->default(0);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->decimal('harga_sewa_harian', 12, 2);
            $table->decimal('biaya_sewa', 14, 2);
            $table->decimal('harga_listrik_kwh', 12, 2)->nullable();
            $table->decimal('sisa_baterai_kwh', 8, 2)->nullable();
            $table->decimal('biaya_listrik', 14, 2)->default(0);
            $table->decimal('total_biaya', 14, 2);
            $table->string('status', 10)->default('AKTIF');
            $table->timestamps();

            $table->index(['garage_id', 'tanggal_mulai', 'tanggal_selesai']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
