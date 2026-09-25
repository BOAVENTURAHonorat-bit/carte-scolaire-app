<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSecretaire(): bool
    {
        return $this->role === 'secretaire';
    }

    public function isDg(): bool
    {
        return $this->role === 'dg';
    }

    public function isSurveillant(): bool
    {
        return $this->role === 'surveillant';
    }

    public function isDev(): bool
    {
        return $this->role === 'dev';
    }

    /**
     * Accès complet : mêmes droits que le DG, plus la gestion de tous les comptes
     * (y compris les DG), pour intervenir en cas de problème.
     */
    public function hasFullAccess(): bool
    {
        return $this->isDg() || $this->isDev();
    }
}
