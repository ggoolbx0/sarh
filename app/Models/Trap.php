<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trap extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'trap_code',
        'description_ar',
        'description_en',
        'risk_weight',
        'fake_response_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'risk_weight' => 'integer',
            'is_active'   => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the localized trap name.
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Derive risk level from risk_weight (1-10) to human-readable category.
     *
     * 1-3 → low, 4-6 → medium, 7-8 → high, 9-10 → critical
     */
    public function deriveRiskLevel(): string
    {
        return match (true) {
            $this->risk_weight <= 3  => 'low',
            $this->risk_weight <= 6  => 'medium',
            $this->risk_weight <= 8  => 'high',
            default                  => 'critical',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('trap_code', $code);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function interactions(): HasMany
    {
        return $this->hasMany(TrapInteraction::class);
    }
}
