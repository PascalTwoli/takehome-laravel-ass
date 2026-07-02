<?php

namespace App\Actions;

use App\Data\CreateTicketTierData;
use App\Models\TicketTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action class responsible for creating a new ticket tier.
 * 
 * This action encapsulates the business logic for ticket tier creation,
 * keeping controllers thin and focused on HTTP concerns.
 */
class CreateTicketTierAction
{
    /**
     * Execute the action to create a ticket tier.
     * 
     * @param CreateTicketTierData $data Validated data transfer object
     * @return TicketTier The newly created ticket tier
     * 
     * @throws \Throwable If any error occurs during creation
     */
    public function execute(CreateTicketTierData $data): TicketTier
    {
        DB::beginTransaction();

        try {
            $ticketTier = $this->createTicketTier($data);

            DB::commit();

            Log::info('Ticket tier created successfully', [
                'ticket_tier_id' => $ticketTier->id,
                'event_id' => $ticketTier->event_id,
                'name' => $ticketTier->name,
            ]);

            return $ticketTier;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to create ticket tier', [
                'event_id' => $data->event_id,
                'name' => $data->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create the ticket tier from validated data.
     * 
     * @param CreateTicketTierData $data
     * @return TicketTier
     */
    private function createTicketTier(CreateTicketTierData $data): TicketTier
    {
        return TicketTier::create([
            'event_id' => $data->event_id,
            'name' => $data->name,
            'price' => $data->price,
            'quantity' => $data->quantity,
            'sales_channels' => $data->sales_channels,
            'is_active' => $data->is_active,
            'is_published' => false, // Always false on creation
        ]);
    }
}
