<?php

namespace App\Actions;

use App\Data\UpdateTicketTierData;
use App\Models\TicketTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\Optional;

/**
 * Action class responsible for updating an existing ticket tier.
 * 
 * Handles partial updates gracefully using Laravel Data's Optional type.
 */
class UpdateTicketTierAction
{
    /**
     * Execute the action to update a ticket tier.
     * 
     * @param TicketTier $ticketTier The ticket tier to update
     * @param UpdateTicketTierData $data Validated data transfer object
     * @return TicketTier The updated ticket tier
     * 
     * @throws \Throwable If any error occurs during update
     */
    public function execute(TicketTier $ticketTier, UpdateTicketTierData $data): TicketTier
    {
        DB::beginTransaction();

        try {
            $this->updateTicketTier($ticketTier, $data);

            DB::commit();

            Log::info('Ticket tier updated successfully', [
                'ticket_tier_id' => $ticketTier->id,
                'event_id' => $ticketTier->event_id,
                'name' => $ticketTier->name,
            ]);

            return $ticketTier->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to update ticket tier', [
                'ticket_tier_id' => $ticketTier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Update the ticket tier with validated data.
     * Only updates fields that are not Optional.
     * 
     * @param TicketTier $ticketTier
     * @param UpdateTicketTierData $data
     * @return void
     */
    private function updateTicketTier(TicketTier $ticketTier, UpdateTicketTierData $data): void
    {
        $attributes = $this->buildUpdateAttributes($data);

        if (!empty($attributes)) {
            $ticketTier->update($attributes);
        }
    }

    /**
     * Build array of attributes to update, excluding Optional values.
     * 
     * @param UpdateTicketTierData $data
     * @return array<string, mixed>
     */
    private function buildUpdateAttributes(UpdateTicketTierData $data): array
    {
        $attributes = [];

        if (!($data->event_id instanceof Optional)) {
            $attributes['event_id'] = $data->event_id;
        }

        if (!($data->name instanceof Optional)) {
            $attributes['name'] = $data->name;
        }

        if (!($data->price instanceof Optional)) {
            $attributes['price'] = $data->price;
        }

        if (!($data->quantity instanceof Optional)) {
            $attributes['quantity'] = $data->quantity;
        }

        if (!($data->sales_channels instanceof Optional)) {
            $attributes['sales_channels'] = $data->sales_channels;
        }

        if (!($data->is_active instanceof Optional)) {
            $attributes['is_active'] = $data->is_active;
        }

        return $attributes;
    }
}
