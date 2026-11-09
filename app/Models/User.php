<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_hash',
        'password',
    ];

    protected $hidden = [
        'password',
        'email_hash',
    ];

    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'password' => 'hashed',
        ];
    }

    /**
     * Hitung otomatis SHA-256 dari email untuk pencarian login karena kolom email terenkripsi
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('email') && ! empty($user->email)) {
                $user->email_hash = hash('sha256', strtolower(trim((string) $user->email)));
            }
        });
    }
}
