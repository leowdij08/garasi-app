<?php

namespace App\Models;

use App\Enums\StatusGarasi;
use App\Enums\TipeGarasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Garage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'tipe_garasi', 'harga_sewa_harian', 'harga_listrik_kwh', 'status'];

    protected function casts(): array
    {
        return [
            'tipe_garasi' => TipeGarasi::class,
            'status' => StatusGarasi::class,
            'harga_sewa_harian' => 'decimal:2',
            'harga_listrik_kwh' => 'decimal:2',
        ];
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}