<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhistleblowerReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'encrypted_content',
        'category',
        'severity',
        'status',
        'anonymous_token',
        'encrypted_evidence_paths',
        'assigned_to',
        'investigator_notes',
        'resolved_at',
        'resolution_outcome',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function assignedInvestigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /*
    |--------------------------------------------------------------------------
    | ENCRYPTION HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Encrypt and store the report content.
     */
    public function setContent(string $plainText): self
    {
        $this->encrypted_content = encrypt($plainText);
        return $this;
    }

    /**
     * Decrypt and retrieve the report content.
     */
    public function getContent(): string
    {
        return decrypt($this->encrypted_content);
    }

    /**
     * Generate a unique anonymous ticket number.
     */
    public static function generateTicketNumber(): string
    {
        return 'WB-' . strtoupper(bin2hex(random_bytes(4))) . '-' . now()->format('ymd');
    }

    /**
     * Generate a hashed anonymous token for follow-up.
     */
    public static function generateAnonymousToken(): string
    {
        return hash('sha256', random_bytes(32) . microtime(true));
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->whereIn('status', ['new', 'under_review']);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
}
