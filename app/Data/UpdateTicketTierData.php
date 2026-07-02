<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateTicketTierData extends Data
{
    /**
     * Allowed sales channel values.
     */
    public const ALLOWED_CHANNELS = ['web', 'box_office', 'mobile', 'partner'];

    public function __construct(
        #[Exists('events', 'id')]
        public int|Optional $event_id = new Optional(),

        #[StringType]
        public string|Optional $name = new Optional(),

        #[Numeric, Min(0)]
        public float|Optional $price = new Optional(),

        #[Min(1)]
        public int|Optional $quantity = new Optional(),

        public array|null|Optional $sales_channels = new Optional(),

        public bool|Optional $is_active = new Optional(),
    ) {
    }

    /**
     * Define validation rules for the data object.
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                // Per-event uniqueness: name must be unique within the event
                function ($attribute, $value, $fail) use ($context) {
                    // Get the ticket tier ID from the route or context
                    $ticketTierId = request()->route('ticket_tier')?->id 
                        ?? request()->route('id')
                        ?? $context->payload['id'] 
                        ?? null;

                    // Get event_id from payload or from existing ticket tier
                    $eventId = $context->payload['event_id'] ?? null;
                    
                    if (!$eventId && $ticketTierId) {
                        $tier = \App\Models\TicketTier::find($ticketTierId);
                        $eventId = $tier?->event_id;
                    }

                    if (!$eventId) {
                        return;
                    }

                    $query = \App\Models\TicketTier::where('event_id', $eventId)
                        ->where('name', $value);

                    // Exclude current tier from uniqueness check
                    if ($ticketTierId) {
                        $query->where('id', '!=', $ticketTierId);
                    }

                    if ($query->exists()) {
                        $fail(__('validation.unique_per_event', [
                            'attribute' => $attribute,
                        ]));
                    }
                },
            ],
            'sales_channels' => [
                'nullable',
                'array',
            ],
            'sales_channels.*' => [
                'string',
                In::create(self::ALLOWED_CHANNELS),
            ],
        ];
    }
}
