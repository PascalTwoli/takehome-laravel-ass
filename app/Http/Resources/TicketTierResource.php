<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for transforming TicketTier models into JSON responses.
 * 
 * Uses whenHas for conditional fields and whenLoaded for relationships
 * to avoid N+1 queries and unnecessary data exposure.
 */
class TicketTierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->whenHas('event_id'),
            'name' => $this->whenHas('name'),
            'price' => $this->whenHas('price'),
            'quantity' => $this->whenHas('quantity'),
            'sales_channels' => $this->whenHas('sales_channels'),
            'is_published' => $this->whenHas('is_published'),
            'is_active' => $this->whenHas('is_active'),
            'created_at' => $this->whenHas('created_at'),
            'updated_at' => $this->whenHas('updated_at'),
            
            // Relationships
            'event' => $this->whenLoaded('event'),
        ];
    }
}
