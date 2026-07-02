<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::create([
            'name' => 'Tech Conference 2026',
            'description' => 'Annual technology conference featuring the latest innovations',
            'starts_at' => now()->addMonths(2),
            'ends_at' => now()->addMonths(2)->addDays(3),
        ]);

        Event::create([
            'name' => 'Summer Music Festival',
            'description' => 'Three-day outdoor music festival with multiple stages',
            'starts_at' => now()->addMonths(3),
            'ends_at' => now()->addMonths(3)->addDays(2),
        ]);

        Event::create([
            'name' => 'Laravel Meetup Nairobi',
            'description' => 'Monthly Laravel developer meetup in Nairobi',
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addHours(4),
        ]);
    }
}
