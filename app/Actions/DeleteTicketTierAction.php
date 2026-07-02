<?php

namespace App\Actions;

use App\Models\TicketTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action class responsible for soft-deleting a ticket tier.
 * 
 * Uses Laravel's soft delete functionality to maintain data integrity
 * and allow for potential recovery.
 */
class DeleteTicketTierAction
{
    /**
     * Execute the action to soft-delete a ticket tier.
     * 
     * @param TicketTier $ticketTier The ticket tier to delete
     * @return void
     * 
     * @throws \Throwable If any error occurs during deletion
     */
    public function execute(TicketTier $ticketTier): void
    {
        DB::beginTransaction();

        try {
            $ticketTierId = $ticketTier->id;
            $eventId = $ticketTier->event_id;
            $name = $ticketTier->name;

            $this->softDeleteTicketTier($ticketTier);

            DB::commit();

            Log::info('Ticket tier soft-deleted successfully', [
                'ticket_tier_id' => $ticketTierId,
                'event_id' => $eventId,
                'name' => $name,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to delete ticket tier', [
                'ticket_tier_id' => $ticketTier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Perform the soft delete operation.
     * 
     * @param TicketTier $ticketTier
     * @return void
     */
    private function softDeleteTicketTier(TicketTier $ticketTier): void
    {
        $ticketTier->delete();
    }
}
