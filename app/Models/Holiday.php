<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'date',
        'type',
        'is_recurring',
        'branch_id',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Check if a given date is a holiday (optionally for a specific branch).
     */
    public static function isHoliday(\Carbon\Carbon $date, ?int $branchId = null): bool
    {
        return static::where('date', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('branch_id')->when($branchId, fn ($q, $id) => $q->orWhere('branch_id', $id)))
            ->exists();
    }
}
