<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'source',
        'sourceable_type',
        'sourceable_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    public function scopeDeducted($query)
    {
        return $query->where('type', 'deducted');
    }
}
