<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateTicketTierAction;
use App\Actions\DeleteTicketTierAction;
use App\Actions\PublishTicketTierAction;
use App\Actions\UpdateTicketTierAction;
use App\Data\CreateTicketTierData;
use App\Data\UpdateTicketTierData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\TicketTierResource;
use App\Models\TicketTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * API Controller for Ticket Tier management.
 * 
 * Follows clean architecture principles:
 * - Authorization first (via policies)
 * - All writes wrapped in transactions
 * - Business logic delegated to Action classes
 * - Consistent response envelopes
 * - Proper exception handling and logging
 */
class TicketTierController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of ticket tiers.
     * 
     * Query Parameters:
     * - filter[event_id]: Filter by event ID
     * - filter[channel]: Filter by sales channel availability
     * - sort: Sort by name, price, or created_at (prefix with - for desc)
     * - include: Include event relationship
     * - per_page: Results per page (default: 15)
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', TicketTier::class);

        $ticketTiers = QueryBuilder::for(TicketTier::class)
            ->allowedFilters([
                'event_id',
                AllowedFilter::callback('channel', function ($query, $value) {
                    $query->availableOnChannel($value);
                }),
            ])
            ->allowedSorts(['name', 'price', 'created_at'])
            ->allowedIncludes(['event'])
            ->defaultSort('-created_at') // Latest first
            ->paginate(request('per_page', 15));

        return response()->json(TicketTierResource::collection($ticketTiers));
    }

    /**
     * Store a newly created ticket tier.
     * 
     * @param CreateTicketTierData $data Validated data transfer object
     * @param CreateTicketTierAction $action Business logic handler
     * @return JsonResponse
     */
    public function store(CreateTicketTierData $data, CreateTicketTierAction $action): JsonResponse
    {
        $this->authorize('create', TicketTier::class);

        DB::beginTransaction();

        try {
            $ticketTier = $action->execute($data);

            DB::commit();

            return ApiResponse::success(
                __('messages.ticket_tier_created'),
                new TicketTierResource($ticketTier),
                201
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e; // Re-throw validation exceptions to preserve error details
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Failed to create ticket tier in controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error(
                __('messages.ticket_tier_creation_failed'),
                500
            );
        }
    }

    /**
     * Display the specified ticket tier.
     * 
     * @param TicketTier $ticketTier
     * @return JsonResponse
     */
    public function show(TicketTier $ticketTier): JsonResponse
    {
        $this->authorize('view', $ticketTier);

        // Load relationships if requested
        if (request()->has('include')) {
            $ticketTier->load(request('include'));
        }

        return response()->json(new TicketTierResource($ticketTier));
    }

    /**
     * Update the specified ticket tier.
     * 
     * @param UpdateTicketTierData $data Validated data transfer object
     * @param TicketTier $ticketTier
     * @param UpdateTicketTierAction $action Business logic handler
     * @return JsonResponse
     */
    public function update(
        UpdateTicketTierData $data,
        TicketTier $ticketTier,
        UpdateTicketTierAction $action
    ): JsonResponse {
        $this->authorize('update', $ticketTier);

        DB::beginTransaction();

        try {
            $updatedTier = $action->execute($ticketTier, $data);

            DB::commit();

            return ApiResponse::success(
                __('messages.ticket_tier_updated'),
                new TicketTierResource($updatedTier)
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Failed to update ticket tier in controller', [
                'ticket_tier_id' => $ticketTier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error(
                __('messages.ticket_tier_update_failed'),
                500
            );
        }
    }

    /**
     * Soft delete the specified ticket tier.
     * 
     * @param TicketTier $ticketTier
     * @param DeleteTicketTierAction $action Business logic handler
     * @return JsonResponse
     */
    public function destroy(TicketTier $ticketTier, DeleteTicketTierAction $action): JsonResponse
    {
        $this->authorize('delete', $ticketTier);

        DB::beginTransaction();

        try {
            $action->execute($ticketTier);

            DB::commit();

            return ApiResponse::success(
                __('messages.ticket_tier_deleted'),
                null,
                204
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Failed to delete ticket tier in controller', [
                'ticket_tier_id' => $ticketTier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error(
                __('messages.ticket_tier_deletion_failed'),
                500
            );
        }
    }

    /**
     * Publish the specified ticket tier to make it available for sale.
     * 
     * @param TicketTier $ticketTier
     * @param PublishTicketTierAction $action Business logic handler
     * @return JsonResponse
     */
    public function publish(TicketTier $ticketTier, PublishTicketTierAction $action): JsonResponse
    {
        $this->authorize('publish', $ticketTier);

        DB::beginTransaction();

        try {
            $publishedTier = $action->execute($ticketTier);

            DB::commit();

            return ApiResponse::success(
                __('messages.ticket_tier_published'),
                new TicketTierResource($publishedTier)
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Failed to publish ticket tier in controller', [
                'ticket_tier_id' => $ticketTier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error(
                __('messages.ticket_tier_publish_failed'),
                500
            );
        }
    }
}

