<?php

namespace App\Policies;

use App\Models\TicketTier;
use App\Models\User;

/**
 * Policy for authorizing TicketTier operations.
 * 
 * Uses Spatie Laravel Permission package to check user permissions.
 * Permission names follow the pattern: action-resource (e.g., view-ticket-tiers)
 */
class TicketTierPolicy
{
    /**
     * Determine whether the user can view any ticket tiers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-ticket-tiers');
    }

    /**
     * Determine whether the user can view the ticket tier.
     */
    public function view(User $user, TicketTier $ticketTier): bool
    {
        return $user->hasPermissionTo('view-ticket-tiers');
    }

    /**
     * Determine whether the user can create ticket tiers.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-ticket-tiers');
    }

    /**
     * Determine whether the user can update the ticket tier.
     */
    public function update(User $user, TicketTier $ticketTier): bool
    {
        return $user->hasPermissionTo('update-ticket-tiers');
    }

    /**
     * Determine whether the user can delete the ticket tier.
     */
    public function delete(User $user, TicketTier $ticketTier): bool
    {
        return $user->hasPermissionTo('delete-ticket-tiers');
    }

    /**
     * Determine whether the user can publish the ticket tier.
     * 
     * Custom authorization for the publish action.
     */
    public function publish(User $user, TicketTier $ticketTier): bool
    {
        return $user->hasPermissionTo('publish-ticket-tiers');
    }
}

