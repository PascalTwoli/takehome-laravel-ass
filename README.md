# Ticket Tier Management API

A Laravel 10 REST API for managing ticket tiers for events. Built with clean architecture principles, comprehensive test coverage, and modern Laravel best practices.

## Overview

This API allows event organizers to create, manage, and publish ticket tiers with features like:
- Multi-channel sales support (web, box office, mobile, partner)
- Per-event name uniqueness
- Publishing workflow
- Soft deletes
- Advanced filtering and sorting
- Role-based permissions

## Tech Stack

- **Framework**: Laravel 10.x
- **PHP**: 8.1+
- **Database**: SQLite (development) / MySQL/PostgreSQL (production)
- **Testing**: Pest PHP
- **Authentication**: Laravel Sanctum
- **Packages**:
  - `spatie/laravel-data` v4 - Type-safe data transfer objects
  - `spatie/laravel-query-builder` v5.8 - Advanced API filtering
  - `spatie/laravel-permission` v6 - Role-based access control

## Architecture

### Clean Architecture Pattern

```
├── Actions/          # Business logic handlers (single responsibility)
├── Data/             # Validated DTOs with type safety
├── Models/           # Eloquent models with relationships and scopes
├── Policies/         # Authorization rules
├── Resources/        # API response transformers
└── Http/Controllers/ # Thin controllers (authorization + delegation)
```

### Key Design Decisions

1. **Action Classes**: Encapsulate business logic with transaction handling and logging
2. **Data Classes**: Type-safe validation using Spatie Data with Laravel validation rules
3. **API Resources**: Consistent response transformation with conditional fields
4. **Query Builder Integration**: Advanced filtering without polluting controller logic
5. **Policy-First Authorization**: All endpoints check permissions before execution

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer 2.x
- Laravel Herd (recommended) or PHP development server

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd takehome-laravel-ass
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database** (`.env`)
   ```env
   DB_CONNECTION=sqlite
   # DB_DATABASE will use database/database.sqlite
   ```

5. **Run migrations and seed data**
   ```bash
   php artisan migrate:fresh --seed
   ```

   This creates:
   - Database schema (events, ticket_tiers, permissions)
   - 3 sample events
   - Test user: `test@example.com` / `password` (with all permissions)

6. **Start development server**
   ```bash
   php artisan serve
   # API available at http://localhost:8000
   ```

## API Endpoints

All endpoints require authentication via Sanctum bearer token.

### Authentication

```http
POST /api/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password"
}

Response:
{
  "token": "1|xxxxx..."
}
```

### Ticket Tiers

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/api/ticket-tiers` | view-ticket-tiers | List all tiers (filtered, sorted, paginated) |
| GET | `/api/ticket-tiers/{id}` | view-ticket-tiers | Get single tier |
| POST | `/api/ticket-tiers` | create-ticket-tiers | Create new tier |
| PUT | `/api/ticket-tiers/{id}` | update-ticket-tiers | Update tier |
| DELETE | `/api/ticket-tiers/{id}` | delete-ticket-tiers | Soft-delete tier |
| POST | `/api/ticket-tiers/{id}/publish` | publish-ticket-tiers | Publish tier |

### Query Parameters (Index Endpoint)

**Filtering:**
```http
GET /api/ticket-tiers?filter[event_id]=1
GET /api/ticket-tiers?filter[channel]=web
```

**Sorting:**
```http
GET /api/ticket-tiers?sort=price          # Ascending
GET /api/ticket-tiers?sort=-created_at    # Descending (latest first)
```

**Including Relationships:**
```http
GET /api/ticket-tiers?include=event
```

**Pagination:**
```http
GET /api/ticket-tiers?per_page=25
```

**Combining:**
```http
GET /api/ticket-tiers?filter[event_id]=1&sort=-price&include=event&per_page=10
```

### Request/Response Examples

**Create Ticket Tier:**
```json
POST /api/ticket-tiers
Authorization: Bearer {token}

{
  "event_id": 1,
  "name": "Early Bird",
  "price": 50.00,
  "quantity": 100,
  "sales_channels": ["web", "mobile"],
  "is_active": true
}

Response (201):
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
    "created_at": "2026-07-03T00:00:00.000000Z",
    "updated_at": "2026-07-03T00:00:00.000000Z"
  }
}
```

**Update Ticket Tier (Partial):**
```json
PUT /api/ticket-tiers/1
Authorization: Bearer {token}

{
  "price": 40.00,
  "quantity": 150
}

Response (200):
{
  "success": true,
  "message": "Ticket tier updated successfully",
  "data": {
    "id": 1,
    "name": "Early Bird",
    "price": "40.00",
    "quantity": 150,
    ...
  }
}
```

**Publish Ticket Tier:**
```json
POST /api/ticket-tiers/1/publish
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "Ticket tier published successfully",
  "data": {
    "id": 1,
    "is_published": true,
    ...
  }
}
```

## Validation Rules

| Field | Rules |
|-------|-------|
| event_id | Required, exists in events table |
| name | Required, string, max 255, unique per event |
| price | Required, numeric, min 0 |
| quantity | Required, integer, min 1 |
| sales_channels | Optional, array, values in [web, box_office, mobile, partner] |
| is_active | Optional, boolean |

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
php artisan test --filter=TicketTierTest
```

### Test Coverage

- ✅ Store creates ticket tier with validation
- ✅ Name uniqueness per event (not global)
- ✅ availableOnChannel scope (NULL = all channels)
- ✅ Publish action flips is_published flag
- ✅ Soft-delete functionality
- ✅ Index filtering by event_id and channel
- ✅ Update with partial data (Optional fields)
- ✅ Validation error handling

**Current Status**: 10/10 tests passing (63 assertions)

## Database Schema

### Events Table
```sql
- id (primary key)
- name (string)
- description (text, nullable)
- starts_at (datetime)
- ends_at (datetime)
- timestamps
```

### Ticket Tiers Table
```sql
- id (primary key)
- event_id (foreign key -> events.id, cascade on delete)
- name (string, unique per event)
- price (decimal 10,2)
- quantity (integer)
- sales_channels (json, nullable - null means all channels)
- is_published (boolean, default false)
- is_active (boolean, default true)
- timestamps
- soft deletes (deleted_at)
- index on (event_id, is_active)
```

## Permissions

The API uses Spatie Laravel Permission for role-based access:

- `view-ticket-tiers` - View ticket tiers
- `create-ticket-tiers` - Create new tiers
- `update-ticket-tiers` - Modify existing tiers
- `delete-ticket-tiers` - Delete tiers (soft-delete)
- `publish-ticket-tiers` - Publish tiers for sale

## Security Features

- ✅ Authentication via Sanctum bearer tokens
- ✅ Authorization policies on all endpoints
- ✅ Mass assignment protection
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ CSRF protection (API token-based)
- ✅ Input validation and sanitization
- ✅ Rate limiting on API routes (60 requests/minute)

## Development

### Code Quality

```bash
# Run tests
php artisan test

# Check code style (if configured)
./vendor/bin/pint

# Static analysis (if configured)
./vendor/bin/phpstan analyse
```

### Git Workflow

This project uses feature branch workflow:
- `main` - Production-ready code
- `feature/*` - New features
- `bugfix/*` - Bug fixes

All changes merged via merge commits to preserve history.

## Project Structure

```
app/
├── Actions/
│   ├── CreateTicketTierAction.php
│   ├── UpdateTicketTierAction.php
│   ├── DeleteTicketTierAction.php
│   └── PublishTicketTierAction.php
├── Data/
│   ├── CreateTicketTierData.php
│   └── UpdateTicketTierData.php
├── Http/
│   ├── Controllers/Api/
│   │   └── TicketTierController.php
│   └── Resources/
│       ├── ApiResponse.php
│       └── TicketTierResource.php
├── Models/
│   ├── Event.php
│   └── TicketTier.php
└── Policies/
    └── TicketTierPolicy.php

database/
├── migrations/
│   ├── 2026_07_02_212634_create_events_table.php
│   ├── 2026_07_02_213026_create_ticket_tiers_table.php
│   └── 2026_07_02_225812_create_permission_tables.php
└── seeders/
    ├── EventSeeder.php
    └── PermissionSeeder.php

tests/
└── Feature/
    └── TicketTierTest.php
```

## Future Enhancements

- [ ] Inventory management (track sold tickets)
- [ ] Price tiers and discounts
- [ ] Bulk operations API
- [ ] Event scheduling and time-based availability
- [ ] Analytics and reporting endpoints
- [ ] Webhook notifications for status changes
- [ ] Multi-currency support
- [ ] API versioning (v2)

## License

This project is proprietary and confidential.

## Author

**Pascal Twoli**  
Email: pascaltwoli@gmail.com  
GitHub: [@PascalTwoli](https://github.com/PascalTwoli)

---

**Built with ❤️ using Laravel 10**
