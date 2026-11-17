<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message_text',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'attachment_mime',
        'attachment_size',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'attachment_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope for direct 1-on-1 private conversation between two users.
     */
    public function scopeBetweenUsers(Builder $query, int $userA, int $userB): Builder
    {
        return $query->where(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userA)->where('receiver_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userB)->where('receiver_id', $userA);
        });
    }

    /**
     * Scope for Factory General Group Channel.
     */
    public function scopeGroupChannel(Builder $query): Builder
    {
        return $query->whereNull('receiver_id');
    }

    public function hasAttachment(): bool
    {
        return !empty($this->attachment_path);
    }

    public function isImage(): bool
    {
        return $this->attachment_type === 'image';
    }

    public function isAudio(): bool
    {
        return $this->attachment_type === 'audio';
    }

    public function isPdf(): bool
    {
        return $this->attachment_type === 'pdf';
    }

    public function formattedSize(): string
    {
        if (!$this->attachment_size) {
            return '';
        }
        if ($this->attachment_size >= 1048576) {
            return round($this->attachment_size / 1048576, 1) . ' MB';
        }
        return round($this->attachment_size / 1024, 0) . ' KB';
    }

    public function formattedTime(): string
    {
        if (!$this->created_at) {
            return '';
        }
        if ($this->created_at->isToday()) {
            return $this->created_at->format('h:i A');
        }
        if ($this->created_at->isYesterday()) {
            return 'Yesterday ' . $this->created_at->format('h:i A');
        }
        return $this->created_at->format('d M, h:i A');
    }
}
