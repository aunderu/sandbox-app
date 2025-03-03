<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Modules\Sandbox\Models\SchoolModel;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    const ROLE_SUPERADMIN = 'SUPERADMIN';
    const ROLE_SCHOOLADMIN = 'SCHOOLADMIN';
    const ROLE_OFFICER = 'OFFICER';

    const ROLE_USER = 'USER';

    const ROLE_DEFAULT = self::ROLE_USER;

    const ROLES = [
        self::ROLE_SUPERADMIN => 'Super Admin',
        self::ROLE_SCHOOLADMIN => 'School Admin',
        self::ROLE_OFFICER => 'Officer',
        self::ROLE_USER => 'User',
    ];

    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }
    public function isSchoolAdmin()
    {
        return $this->role === self::ROLE_SCHOOLADMIN;
    }
    public function isOfficer()
    {
        return $this->role === self::ROLE_OFFICER;
    }
    public function isUser()
    {
        return $this->role === self::ROLE_USER;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin()
            || $this->isSchoolAdmin()
            || $this->isOfficer()
            || $this->isUser();
        // return true;
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'school_id',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    public function school()
    {
        return $this->belongsTo(SchoolModel::class, 'school_id', 'school_id');
    }
}
