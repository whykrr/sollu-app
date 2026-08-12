<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Build a success response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return JsonResponse
     */
    public function successResponse($data = [], ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [];

        if (!empty($data) || is_array($data)) {
            $response['data'] = $data;
        }

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, $code);
    }

    /**
     * Build an error response.
     *
     * @param string $message
     * @param array|int $errors
     * @param int $code
     * @return JsonResponse
     */
    public function errorResponse(string $message, array|int $errors = [], int $code = 400): JsonResponse
    {
        if (is_int($errors)) {
            $code = $errors;
            $errors = [];
        }

        $response = [
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Build a "No Content" response.
     *
     * @return JsonResponse
     */
    public function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
