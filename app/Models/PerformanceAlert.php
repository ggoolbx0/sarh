<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'alert_type',
        'severity',
        'title_ar',
        'title_en',
        'message_ar',
        'message_en',
        'trigger_data',
        'is_read',
        'read_at',
        'dismissed_by',
    ];

    protected function casts(): array
    {
        return [
            'trigger_data' => 'array',
            'is_read'      => 'boolean',
            'read_at'      => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dismissedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function getMessageAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->message_ar : $this->message_en;
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
}
