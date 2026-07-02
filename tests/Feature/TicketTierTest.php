<?php

use App\Models\Event;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create or get permissions
    Permission::firstOrCreate(['name' => 'view-ticket-tiers']);
    Permission::firstOrCreate(['name' => 'create-ticket-tiers']);
    Permission::firstOrCreate(['name' => 'update-ticket-tiers']);
    Permission::firstOrCreate(['name' => 'delete-ticket-tiers']);
    Permission::firstOrCreate(['name' => 'publish-ticket-tiers']);

    // Create authenticated user with all permissions
    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'view-ticket-tiers',
        'create-ticket-tiers',
        'update-ticket-tiers',
        'delete-ticket-tiers',
        'publish-ticket-tiers',
    ]);

    // Create test events
    $this->event1 = Event::create([
        'name' => 'Tech Conference 2026',
        'description' => 'Annual technology conference',
        'starts_at' => now()->addMonths(2),
        'ends_at' => now()->addMonths(2)->addDays(3),
    ]);

    $this->event2 = Event::create([
        'name' => 'Music Festival',
        'description' => 'Summer music festival',
        'starts_at' => now()->addMonths(3),
        'ends_at' => now()->addMonths(3)->addDays(2),
    ]);
});

/**
 * Test 1: Store creates a tier - assert status, DB row, and response payload
 */
test('store creates a ticket tier successfully', function () {
    $payload = [
        'event_id' => $this->event1->id,
        'name' => 'Early Bird',
        'price' => 50.00,
        'quantity' => 100,
        'sales_channels' => ['web', 'mobile'],
        'is_active' => true,
    ];

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/ticket-tiers', $payload);

    // Assert HTTP status
    $response->assertStatus(201);

    // Assert response structure and payload
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'event_id',
            'name',
            'price',
            'quantity',
            'sales_channels',
            'is_published',
            'is_active',
            'created_at',
            'updated_at',
        ],
    ]);

    $response->assertJson([
        'success' => true,
        'data' => [
            'event_id' => $this->event1->id,
            'name' => 'Early Bird',
            'price' => '50.00',
            'quantity' => 100,
            'sales_channels' => ['web', 'mobile'],
            'is_published' => false,
            'is_active' => true,
        ],
    ]);

    // Assert database state
    $this->assertDatabaseHas('ticket_tiers', [
        'event_id' => $this->event1->id,
        'name' => 'Early Bird',
        'price' => 50.00,
        'quantity' => 100,
        'is_published' => false,
        'is_active' => true,
    ]);

    expect(TicketTier::count())->toBe(1);
});

/**
 * Test 2: Name uniqueness is enforced per event, not globally
 */
test('name uniqueness is enforced per event but same name allowed across events', function () {
    // Create tier for event 1
    TicketTier::create([
        'event_id' => $this->event1->id,
        'name' => 'VIP',
        'price' => 100.00,
        'quantity' => 50,
    ]);

    // Try to create another tier with same name for event 1 - should fail
    $response1 = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/ticket-tiers', [
            'event_id' => $this->event1->id,
            'name' => 'VIP',
            'price' => 150.00,
            'quantity' => 25,
        ]);

    $response1->assertStatus(422);
    $response1->assertJsonValidationErrors(['name']);

    // Create tier with same name for event 2 - should succeed
    $response2 = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/ticket-tiers', [
            'event_id' => $this->event2->id,
            'name' => 'VIP',
            'price' => 200.00,
            'quantity' => 30,
        ]);

    $response2->assertStatus(201);
    
    // Assert both tiers exist with same name but different events
    expect(TicketTier::where('name', 'VIP')->count())->toBe(2);
    expect(TicketTier::where('name', 'VIP')->where('event_id', $this->event1->id)->count())->toBe(1);
    expect(TicketTier::where('name', 'VIP')->where('event_id', $this->event2->id)->count())->toBe(1);
});

/**
 * Test 3: availableOnChannel scope returns correct tiers
 */
test('availableOnChannel scope returns all-channel and matching channel tiers', function () {
    // Create tier available on all channels (NULL)
    $allChannelsTier = TicketTier::create([
        'event_id' => $this->event1->id,
        'name' => 'General Admission',
        'price' => 30.00,
        'quantity' => 500,
        'sales_channels' => null, // Available on all channels
    ]);

    // Create tier available only on web and mobile
    $webMobileTier = TicketTier::create([
        'event_id' => $this->event1->id,
        'name' => 'Online Only',
        'price' => 40.00,
        'quantity' => 200,
        'sales_channels' => ['web', 'mobile'],
    ]);

    // Create tier available only on box_office
    $boxOfficeTier = TicketTier::create([
        'event_id' => $this->event1->id,
        'name' => 'Box Office Only',
        'price' => 50.00,
        'quantity' => 100,
        'sales_channels' => ['box_office'],
    ]);

    // Query for tiers available on 'web'
    $webTiers = TicketTier::availableOnChannel('web')->get();

    // Should return all-channels tier and web/mobile tier, but not box_office tier
    expect($webTiers->count())->toBe(2);
    expect($webTiers->pluck('id')->toArray())->toContain($allChannelsTier->id);
    expect($webTiers->pluck('id')->toArray())->toContain($webMobileTier->id);
    expect($webTiers->pluck('id')->toArray())->not->toContain($boxOfficeTier->id);

    // Query for tiers available on 'box_office'
    $boxOfficeTiers = TicketTier::availableOnChannel('box_office')->get();

    // Should return all-channels tier and box_office tier, but not web/mobile tier
    expect($boxOfficeTiers->count())->toBe(2);
    expect($boxOfficeTiers->pluck('id')->toArray())->toContain($allChannelsTier->id);
    expect($boxOfficeTiers->pluck('id')->toArray())->toContain($boxOfficeTier->id);
    expect($boxOfficeTiers->pluck('id')->toArray())->not->toContain($webMobileTier->id);
});

/**
 * Test 4: Publish action flips is_published to true
 */
test('publish action sets is_published to true', function () {
    $tier = TicketTier::create([
        'event_id' => $this->event1->id,
        'name' => 'Early Bird',
        'price' => 50.00,
        'quantity' => 100,
        'is_published' => false,
    ]);

    expect($tier->is_published)->toBeFalse();

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/ticket-tiers/{$tier->id}/publish");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'id' => $tier->id,
            'is_published' => true,
        ],
    ]);

    // Assert database state
    $this->assertDatabaseHas('ticket_tiers', [
        'id' => $tier->id,
        'is_published' => true,
    ]);

    expect($tier->fresh()->is_published)->toBeTrue();
});

/**
 * Test 5: Destroy soft-deletes the tier
 */
test('destroy soft-deletes ticket tier and excludes from index', function () {
    $tier = TicketTier::create([
        'event_id' => $this->event1->id,
        'name' => 'VIP',
        'price' => 100.00,
        'quantity' => 50,
    ]);

    expect(TicketTier::count())->toBe(1);
    expect(TicketTier::withTrashed()->count())->toBe(1);

    // Delete the tier
    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/ticket-tiers/{$tier->id}");

    $response->assertStatus(204);

    // Assert tier is soft-deleted (deleted_at is set)
    $this->assertSoftDeleted('ticket_tiers', [
        'id' => $tier->id,
    ]);

    // Assert tier is excluded from normal queries
    expect(TicketTier::count())->toBe(0);
    
    // Assert tier still exists with trashed
    expect(TicketTier::withTrashed()->count())->toBe(1);
    expect($tier->fresh()->trashed())->toBeTrue();

    // Assert tier is not in index endpoint
    $indexResponse = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/ticket-tiers');

    $indexResponse->assertStatus(200);
    $data = $indexResponse->json();
    expect(count($data))->toBe(0);
});

/**
 * Additional Test: Index endpoint with filters and sorting
 */
test('index endpoint supports filtering by event_id and channel', function () {
    // Create tiers for different events and channels
    TicketTier::create([
        'event_id' => $this->event1->id,
        'name' => 'Event 1 - Web',
        'price' => 50.00,
        'quantity' => 100,
        'sales_channels' => ['web'],
    ]);

    TicketTier::create([
        'event_id' => $this->event2->id,
        'name' => 'Event 2 - All',
        'price' => 60.00,
        'quantity' => 150,
        'sales_channels' => null,
    ]);

    // Filter by event_id
    $response1 = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/ticket-tiers?filter[event_id]=' . $this->event1->id);

    $response1->assertStatus(200);
    $data1 = $response1->json();
    expect(count($data1))->toBe(1);
    expect($data1[0]['event_id'])->toBe($this->event1->id);

    // Filter by channel
    $response2 = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/ticket-tiers?filter[channel]=web');

    $response2->assertStatus(200);
    $data2 = $response2->json();
    expect(count($data2))->toBe(2); // Both tiers available on web
});

/**
 * Additional Test: Update endpoint
 */
test('update endpoint modifies ticket tier', function () {
    $tier = TicketTier::create([
        'event_id' => $this->event1->id,
        'name' => 'Early Bird',
        'price' => 50.00,
        'quantity' => 100,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->putJson("/api/ticket-tiers/{$tier->id}", [
            'name' => 'Super Early Bird',
            'price' => 40.00,
        ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'id' => $tier->id,
            'name' => 'Super Early Bird',
            'price' => '40.00',
            'quantity' => 100, // Unchanged
        ],
    ]);

    $this->assertDatabaseHas('ticket_tiers', [
        'id' => $tier->id,
        'name' => 'Super Early Bird',
        'price' => 40.00,
        'quantity' => 100,
    ]);
});

/**
 * Additional Test: Validation
 */
test('validation errors are returned correctly', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/ticket-tiers', [
            'event_id' => 999, // Non-existent event
            'name' => '',
            'price' => -10, // Negative price
            'quantity' => 0, // Below minimum
            'sales_channels' => ['invalid_channel'],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['event_id', 'name', 'price', 'quantity', 'sales_channels.0']);
});
