<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->string('id', 4)->primary();
            $table->string('tipe_garasi', 10);
            $table->decimal('harga_sewa_harian', 12, 2);
            $table->decimal('harga_listrik_kwh', 12, 2)->nullable();
            $table->string('status', 10)->default('AVAILABLE');
            $table->timestamps();

            $table->index('tipe_garasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
