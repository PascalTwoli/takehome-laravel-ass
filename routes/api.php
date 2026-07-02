<?php

use App\Http\Controllers\Api\TicketTierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Ticket Tier API Routes
|--------------------------------------------------------------------------
|
| RESTful API endpoints for managing ticket tiers.
| All routes require authentication via Sanctum.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Standard resourceful routes
    Route::apiResource('ticket-tiers', TicketTierController::class);
    
    // Custom publish action
    Route::post('ticket-tiers/{ticketTier}/publish', [TicketTierController::class, 'publish'])
        ->name('ticket-tiers.publish');
});
