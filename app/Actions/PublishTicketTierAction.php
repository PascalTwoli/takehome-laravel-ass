<?php

namespace App\Actions;

use App\Models\TicketTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action class responsible for publishing a ticket tier.
 * 
 * Publishing makes a ticket tier available for public sale.
 * This is a business operation separate from activation (is_active).
 */
class PublishTicketTierAction
{
    /**
     * Execute the action to publish a ticket tier.
     * 
     * @param TicketTier $ticketTier The ticket tier to publish
     * @return TicketTier The published ticket tier
     * 
     * @throws \Throwable If any error occurs during publishing
     */
    public function execute(TicketTier $ticketTier): TicketTier
    {
        DB::beginTransaction();

        try {
            $this->publishTicketTier($ticketTier);

            DB::commit();

            Log::info('Ticket tier published successfully', [
                'ticket_tier_id' => $ticketTier->id,
                'event_id' => $ticketTier->event_id,
                'name' => $ticketTier->name,
            ]);

            return $ticketTier->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to publish ticket tier', [
                'ticket_tier_id' => $ticketTier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark the ticket tier as published.
     * 
     * @param TicketTier $ticketTier
     * @return void
     */
    private function publishTicketTier(TicketTier $ticketTier): void
    {
        $ticketTier->update([
            'is_published' => true,
        ]);
    }
}
