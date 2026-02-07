<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrapInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trap_id',
        'trap_type',
        'trap_element',
        'page_url',
        'ip_address',
        'user_agent',
        'interaction_data',
        'risk_level',
        'is_reviewed',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'interaction_data' => 'array',
            'is_reviewed'      => 'boolean',
            'reviewed_at'      => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trap(): BelongsTo
    {
        return $this->belongsTo(Trap::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeUnreviewed($query)
    {
        return $query->where('is_reviewed', false);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }
}
