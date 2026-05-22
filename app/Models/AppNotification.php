<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'related_id',
        'read_at',
        'meta',         // JSON: diff catatan, nama staff, dll.
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'meta'    => 'array',   // otomatis encode/decode JSON
    ];

    // Tipe-tipe notifikasi
    const TYPE_REVISION_REQUESTED = 'revision_requested'; // leader → staff: perlu revisi
    const TYPE_REVISION_SUBMITTED = 'revision_submitted'; // staff → leader: revisi selesai
    const TYPE_AUTO_REJECTED      = 'auto_rejected';      // sistem → leader: timeout auto-reject
    const TYPE_NOT_SUBMITTED      = 'not_submitted';      // sistem → leader: staff tidak kumpul

    // Relasi ke user penerima
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope: notif yang belum dibaca
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // Scope: notif hari ini yang belum dibaca (untuk dashboard card)
    public function scopeTodayUnread($query)
    {
        return $query->whereNull('read_at')
                     ->whereDate('created_at', today());
    }

    // Apakah notif sudah dibaca?
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    // Tandai sebagai sudah dibaca
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    // Helper: ambil nilai dari meta
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }
}
