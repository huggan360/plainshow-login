<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * A PlainShow account. The table is shared with plainshow-core, which edits
 * profiles and roles; login only authenticates.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username', 'email', 'first_name', 'last_name', 'password', 'role', 'status',
        'avatar_path', 'phone', 'pi_username',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function name(): string
    {
        return trim($this->first_name.' '.$this->last_name) ?: $this->username;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return rtrim((string) config('plainshow.core_url'), '/').'/avatars/'.$this->id.'?v='.substr(md5($this->avatar_path), 0, 8);
    }

    /** Let users sign in with either their username or email address. */
    public function findForPassport(string $login): ?self
    {
        return static::where('username', $login)->orWhere('email', $login)->first();
    }
}
