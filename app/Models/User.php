<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // =========================================================================
    // Role Helpers
    // =========================================================================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    /**
     * Check if user has at least the given role level.
     * admin > manager > cashier
     */
    public function hasRole(string $role): bool
    {
        $hierarchy = ['cashier' => 1, 'manager' => 2, 'admin' => 3];
        $userLevel = $hierarchy[$this->role] ?? 0;
        $required  = $hierarchy[$role] ?? 0;
        return $userLevel >= $required;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin'   => '👑 Admin',
            'manager' => '📊 Manager',
            'cashier' => '🛒 Cashier',
            default   => $this->role,
        };
    }

    public function roleBadgeColor(): string
    {
        return match ($this->role) {
            'admin'   => 'purple',
            'manager' => 'blue',
            'cashier' => 'green',
            default   => 'gray',
        };
    }
}
