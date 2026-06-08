<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BackdateRequest extends Model
{
    protected $fillable = [
        'user_id',
        'requested_date',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_note',
        'approval_token',
        'token_expires_at',
    ];

    protected $casts = [
        'requested_date'  => 'date',
        'reviewed_at'     => 'datetime',
        'token_expires_at'=> 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // ─── Helper Methods ───────────────────────────────────────────────────────

    /**
     * Apakah permintaan ini sudah disetujui DAN token-nya masih valid?
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved'
            && $this->token_expires_at
            && $this->token_expires_at->isFuture();
    }

    /**
     * Apakah token approval sudah kedaluwarsa?
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    /**
     * Generate token approval unik + set waktu expiry 24 jam.
     */
    public function generateApprovalToken(): string
    {
        $token = Str::uuid()->toString();
        $this->update([
            'approval_token'   => $token,
            'token_expires_at' => now()->addHours(24),
        ]);
        return $token;
    }

    /**
     * Cari permintaan yang valid berdasarkan token.
     */
    public static function findValidByToken(string $token): ?self
    {
        return static::where('approval_token', $token)
            ->where('status', 'approved')
            ->where('token_expires_at', '>', now())
            ->first();
    }
}
