<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'department', 'division', 'is_management', 'google_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Daftar departemen sesuai Google Form & data HR (Rapat 12 Mei 2026).
     * Single source of truth — dipakai di form, validation, dan UI labels.
     */
    public const DEPARTMENTS = [
        'sales'             => 'Sales',
        'marketing'         => 'Marketing',
        'product_it'        => 'Product / IT',
        'operational'       => 'Operational',
        'hr'                => 'HR',
        'finance'           => 'Finance',
        'ga'                => 'General Affairs',
        'creative'          => 'Creative',
        'customer_support'  => 'Customer Support',
    ];

    public const ROLES = [
        'staff'      => 'Staff',
        'leader'     => 'Leader',
        'c_level'    => 'C-Level',
    ];

    /**
     * Apakah user ini bisa mengakses fitur Export laporan?
     *
     * Aturan (Rapat 17 Mei 2026):
     * - c_level selalu bisa export (semua departemen)
     * - User dengan flag is_management = true juga bisa export (semua departemen)
     *   contoh: Bu Ika (leader), Fanny (staff) — tanpa mengubah role utama mereka
     */
    public function canExport(): bool
    {
        return $this->role === 'c_level' || (bool) $this->is_management;
    }

    public function isManagement(): bool
    {
        return (bool) $this->is_management;
    }

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

    public function getDepartmentLabelAttribute(): ?string
    {
        if (!$this->department) return null;
        return self::DEPARTMENTS[$this->department] ?? ucfirst(str_replace('_', ' ', $this->department));
    }
}
