<?php

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => RoleType::class,
        ];
    }

    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === RoleType::ADMIN;
    }
}