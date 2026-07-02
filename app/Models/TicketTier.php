<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class TicketTier extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'name',
        'price',
        'quantity',
        'sales_channels',
        'is_published',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'sales_channels' => 'array',
        'is_published' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the event that owns the ticket tier.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Scope a query to only include ticket tiers for a specific event.
     */
    public function scopeForEvent(Builder $query, int $eventId): Builder
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Scope a query to only include active ticket tiers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include ticket tiers available on a specific sales channel.
     * 
     * Returns tiers where:
     * - sales_channels is NULL (available on all channels)
     * - OR sales_channels JSON array contains the specified channel
     */
    public function scopeAvailableOnChannel(Builder $query, string $channel): Builder
    {
        return $query->where(function (Builder $q) use ($channel) {
            $q->whereNull('sales_channels')
              ->orWhereJsonContains('sales_channels', $channel);
        });
    }
}
