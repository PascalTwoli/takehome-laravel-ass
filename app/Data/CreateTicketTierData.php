<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CreateTicketTierData extends Data
{
    /**
     * Allowed sales channel values.
     */
    public const ALLOWED_CHANNELS = ['web', 'box_office', 'mobile', 'partner'];

    public function __construct(
        #[Required, Exists('events', 'id')]
        public int $event_id,

        #[Required, StringType]
        public string $name,

        #[Required, Numeric, Min(0)]
        public float $price,

        #[Required, Min(1)]
        public int $quantity,

        public ?array $sales_channels = null,

        public bool $is_active = true,
    ) {
    }

    /**
     * Define validation rules for the data object.
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Per-event uniqueness: name must be unique within the event
                function ($attribute, $value, $fail) use ($context) {
                    $eventId = $context->payload['event_id'] ?? null;
                    
                    if (!$eventId) {
                        return;
                    }

                    $exists = \App\Models\TicketTier::where('event_id', $eventId)
                        ->where('name', $value)
                        ->exists();

                    if ($exists) {
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
