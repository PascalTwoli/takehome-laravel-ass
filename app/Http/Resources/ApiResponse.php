<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Consistent API response envelope for all mutating endpoints.
 * 
 * Provides a uniform structure across the API:
 * {
 *   "success": true,
 *   "message": "Operation completed successfully",
 *   "data": { ... }
 * }
 */
class ApiResponse
{
    /**
     * Create a success response with data.
     *
     * @param string $message Translated user-facing message
     * @param JsonResource|ResourceCollection|array|null $data Response payload
     * @param int $statusCode HTTP status code (default: 200)
     * @return JsonResponse
     */
    public static function success(
        string $message,
        JsonResource|ResourceCollection|array|null $data = null,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Create an error response.
     *
     * @param string $message Translated user-facing error message
     * @param int $statusCode HTTP status code (default: 400)
     * @param array|null $errors Optional validation errors or additional context
     * @return JsonResponse
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
