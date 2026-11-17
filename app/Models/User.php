<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

#[Fillable(['name', 'email', 'role', 'password', 'pin_code', 'last_login_at', 'device_session_token'])]
#[Hidden(['password', 'remember_token', 'pin_code'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function isFinancialAuthorized(): bool
    {
        return in_array($this->role, ['ADMIN', 'ACCOUNTANT', 'MANAGER', 'OWNER'], true);
    }

    public function isFullAdmin(): bool
    {
        return in_array($this->role, ['ADMIN', 'ACCOUNTANT'], true);
    }

    public function isManager(): bool
    {
        return $this->role === 'MANAGER';
    }

    public function isOwner(): bool
    {
        return $this->role === 'OWNER';
    }

    public function isReadOnly(): bool
    {
        return $this->role === 'OWNER';
    }

    public function canAddExpenses(): bool
    {
        return in_array($this->role, ['ADMIN', 'ACCOUNTANT', 'MANAGER'], true);
    }

    public function canEditOperations(): bool
    {
        return in_array($this->role, ['ADMIN', 'ACCOUNTANT'], true);
    }

    public function hasPin(): bool
    {
        return !empty($this->pin_code);
    }

    public function verifyPin(string $pin): bool
    {
        if (empty($this->pin_code)) {
            return false;
        }

        return Hash::check($pin, $this->pin_code) || $this->pin_code === $pin;
    }

    public function setPin(string $pin): void
    {
        $this->pin_code = Hash::make($pin);
        $this->save();
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials ?: 'BK';
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
