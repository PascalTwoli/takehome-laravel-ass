# Laravel Backend Developer Assessment - Implementation Plan

**Candidate:** Pascal Twoli  
**Position:** Laravel Backend Developer Intern - AlienSoft Technologies  
**Deadline:** July 1st, 2026, 14:00 HRS (EAT)  
**Time Budget:** 2-3 hours (focused implementation)

---

## Assessment Overview

Building a clean CRUD API for an events platform's ticket tier management system, demonstrating Laravel best practices and architectural patterns.

### Stack Requirements
- Laravel 10.x
- PHP 8.1+
- Pest (testing)
- Spatie packages:
  - laravel-data v4 (DTOs & validation)
  - laravel-query-builder v5.8 (filtering & sorting)
  - laravel-permission v6 (authorization)

---

## Key Architectural Patterns to Demonstrate

### 1. **Laravel Data Classes** (Input & Validation)
- `CreateTicketTierData` and `UpdateTicketTierData`
- Validation declared in `rules(ValidationContext $context)` method
- Use `Optional` for non-required fields
- Per-event uniqueness validation for tier names

### 2. **Action Classes** (Business Logic)
- `CreateTicketTierAction`
- `UpdateTicketTierAction`
- `DeleteTicketTierAction`
- `PublishTicketTierAction`
- Each with single public `execute(...)` method
- All other methods private
- Controllers must NOT contain write logic

### 3. **API Resources** (Output Transformation)
- `TicketTierResource` with consistent envelope
- Always include `id`
- Use `whenHas` for conditional fields
- Use `whenLoaded` for relations
- No business logic in resources

### 4. **Spatie Query Builder** (Index Endpoint)
- `QueryBuilder::for(...)`
- `allowedFilters`: event_id, channel (via callback)
- `allowedSorts`: name, price, created_at
- `allowedIncludes`: event relationship
- Default `latest()` ordering
- Pagination via `per_page` query param

### 5. **Controller Conventions**
- API resource controller (no create/edit)
- `$this->authorize(...)` first
- Wrap writes in `DB::beginTransaction()` / try-catch
- Re-throw `ValidationException`
- Log other exceptions, throw translated generic message
- Consistent response envelope

### 6. **Authorization Policy**
- `TicketTierPolicy` with standard gates
- Backed by `spatie/laravel-permission`
- Permission checks via `$user->hasPermissionTo(...)`

### 7. **Localization**
- Every user-facing string wrapped in `__()`
- Lang files for messages

### 8. **Pest Feature Tests**
- `beforeEach` with user + permissions bootstrap
- Assert HTTP status, DB state, response payload
- Cover all 5 required scenarios

---

## Database Schema

### `events` table (supporting)
- `id`
- `name`
- `description` (nullable)
- `starts_at`
- `ends_at`
- Timestamps

### `ticket_tiers` table
- `id`
- `event_id` (FK → events)
- `name` (string) - unique per event
- `price` (decimal) - money in currency units
- `quantity` (unsigned int) - total tickets available
- `sales_channels` (json, nullable) - NULL = all channels, array = specific channels
- `is_published` (boolean, default false)
- `is_active` (boolean, default true)
- Timestamps + soft deletes
- **Index:** composite (event_id, is_active)

---

## Model Features

### TicketTier Model
**Casts:**
- `sales_channels` → array
- `price` → decimal/float
- `is_published` → boolean
- `is_active` → boolean

**Relationships:**
- `belongsTo(Event::class)`

**Query Scopes:**
- `forEvent($eventId)` - filter by event
- `active()` - only active tiers
- `availableOnChannel($channel)` - NULL sales_channels OR channel in array

---

## API Endpoints

```
GET    /api/ticket-tiers       - index (filtered, sorted, paginated)
POST   /api/ticket-tiers       - store
GET    /api/ticket-tiers/{id}  - show
PUT    /api/ticket-tiers/{id}  - update
DELETE /api/ticket-tiers/{id}  - destroy (soft delete)
POST   /api/ticket-tiers/{id}/publish - publish
```

### Index Filters
- `filter[event_id]=1` - by event
- `filter[channel]=web` - available on channel
- `sort=name` / `sort=-price` - sorting
- `per_page=15` - pagination
- `include=event` - eager load event

---

## Validation Rules

### CreateTicketTierData
- `name`: required, string, unique per event (not global)
- `event_id`: required, exists:events
- `price`: required, numeric, >= 0
- `quantity`: required, integer, >= 1
- `sales_channels`: nullable, array, values in allowed set (e.g., ['web', 'box_office', 'mobile'])
- `is_active`: boolean, default true

### UpdateTicketTierData
- Same as Create, but all fields optional except for per-event uniqueness check

---

## Testing Scenarios (Pest)

1. ✅ **Store creates a tier** - assert status, DB row, response payload
2. ✅ **Name uniqueness per event** - same name allowed across different events
3. ✅ **availableOnChannel scope** - returns NULL + matching channels, excludes others
4. ✅ **Publish action** - flips is_published to true
5. ✅ **Soft delete** - deleted_at set, excluded from index

---

## Response Envelope Structure

```json
{
  "success": true,
  "message": "Ticket tier created successfully",
  "data": {
    "id": 1,
    "event_id": 1,
    "name": "Early Bird",
    "price": "50.00",
    "quantity": 100,
    "sales_channels": ["web", "mobile"],
    "is_published": false,
    "is_active": true,
    "created_at": "2026-07-01T10:00:00Z",
    "updated_at": "2026-07-01T10:00:00Z"
  }
}
```

---

## Assumptions & Design Decisions

1. **Sales Channels:** Allowed values are: `['web', 'box_office', 'mobile', 'partner']`
2. **Price Storage:** Stored as decimal in base currency units (e.g., USD cents would be whole dollars)
3. **Permissions:** Assumed permission names:
   - `view-ticket-tiers`
   - `create-ticket-tiers`
   - `update-ticket-tiers`
   - `delete-ticket-tiers`
   - `publish-ticket-tiers`
4. **Soft Deletes:** Deleted tiers remain in DB with `deleted_at` timestamp
5. **Per-Event Uniqueness:** Tier name must be unique within an event, but same name can exist across different events
6. **Channel Filtering:** `availableOnChannel` returns tiers where:
   - `sales_channels IS NULL` (sold everywhere)
   - OR `sales_channels` JSON array contains the specified channel
7. **Default Ordering:** Index endpoint defaults to `latest()` (newest first)
8. **Error Handling:** All exceptions except `ValidationException` are logged and return a generic translated error message

---

## Commit Strategy

### Phase 1: Foundation (1 commit)
- Fresh Laravel + Spatie packages + PLAN.md

### Phase 2: Database Layer (3 commits)
- Events migration
- Ticket tiers migration
- TicketTier model + scopes

### Phase 3: Data & Actions (3 commits)
- Data classes
- Create & Update actions
- Delete & Publish actions

### Phase 4: API Layer (3 commits)
- API Resource + envelope
- Policy
- Controller + Query Builder

### Phase 5: Routes & Localization (1 commit)
- API routes + lang files

### Phase 6: Testing (2 commits)
- Basic CRUD tests
- Advanced tests (scopes, publish, soft delete)

### Phase 7: Documentation (1 commit)
- README + Postman collection

**Total:** ~14 meaningful commits showing thought process

---

## Success Criteria (100 points)

- **Data classes & validation (25pts):** Correct v4 usage, Optional, per-event unique rule
- **Action classes (15pts):** Single execute, private helpers, no controller logic
- **Controller & envelope (15pts):** API resource, authorize-first, transactions, consistent envelope
- **Query Builder (15pts):** Filters, sorts, includes, pagination, latest(), availableOnChannel
- **Resource (10pts):** id + whenHas + whenLoaded, no internals leakage
- **Tests (15pts):** Meaningful assertions, 5 scenarios covered and passing
- **Conventions & polish (5pts):** __() everywhere, logging, routing, policy, clean commits, README

---

## Development Environment

- **OS:** Windows 11
- **PHP:** 8.4.22 (via Laravel Herd)
- **Composer:** 2.10.1
- **Database:** SQLite (for simplicity)
- **IDE:** Visual Studio Code with Kiro AI assistant

---

*This plan serves as a roadmap for clean, incremental development with quality commits demonstrating professional Laravel development practices.*
