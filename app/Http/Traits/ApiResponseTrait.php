<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait ApiResponseTrait
{
    /**
     * Return a successful JSON response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(
        $data = null,
        ?string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message ?? 'Operation completed successfully',
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @param mixed $data
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        int $statusCode = 400,
        ?array $errors = null,
        $data = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error JSON response
     *
     * @param array|\Illuminate\Contracts\Support\MessageBag $errors
     * @param string|null $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        $errors,
        ?string $message = null
    ): JsonResponse {
        // Convert MessageBag to array if needed
        if (is_object($errors) && method_exists($errors, 'toArray')) {
            $errors = $errors->toArray();
        }

        return $this->errorResponse(
            $message ?? 'Validation failed',
            422,
            $errors
        );
    }

    /**
     * Return a not found JSON response
     *
     * @param string|null $message
     * @param string|null $resource
     * @return JsonResponse
     */
    protected function notFoundResponse(
        ?string $message = null,
        ?string $resource = null
    ): JsonResponse {
        $defaultMessage = $resource
            ? "{$resource} not found"
            : 'Resource not found';

        return $this->errorResponse(
            $message ?? $defaultMessage,
            404
        );
    }

    /**
     * Return an unauthorized JSON response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(
        ?string $message = null
    ): JsonResponse {
        return $this->errorResponse(
            $message ?? 'Unauthorized access',
            401
        );
    }

    /**
     * Return a forbidden JSON response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(
        ?string $message = null
    ): JsonResponse {
        return $this->errorResponse(
            $message ?? 'Forbidden. You do not have permission to perform this action',
            403
        );
    }

    /**
     * Return a server error JSON response
     *
     * @param string|null $message
     * @param mixed $data
     * @return JsonResponse
     */
    protected function serverErrorResponse(
        ?string $message = null,
        $data = null
    ): JsonResponse {
        return $this->errorResponse(
            $message ?? 'Internal server error',
            500,
            null,
            $data
        );
    }

    /**
     * Return a paginated JSON response
     *
     * @param LengthAwarePaginator|AnonymousResourceCollection $paginator
     * @param string|null $message
     * @param array|null $meta
     * @return JsonResponse
     */
    protected function paginatedResponse(
        $paginator,
        ?string $message = null,
        ?array $meta = null
    ): JsonResponse {
        // Handle Laravel Resource Collections
        if ($paginator instanceof AnonymousResourceCollection) {
            $data = $paginator->response()->getData(true);
            
            $response = [
                'success' => true,
                'message' => $message ?? 'Data retrieved successfully',
                'data' => $data['data'] ?? [],
                'pagination' => [
                    'current_page' => $data['meta']['current_page'] ?? 1,
                    'from' => $data['meta']['from'] ?? null,
                    'last_page' => $data['meta']['last_page'] ?? 1,
                    'per_page' => $data['meta']['per_page'] ?? 15,
                    'to' => $data['meta']['to'] ?? null,
                    'total' => $data['meta']['total'] ?? 0,
                ],
            ];

            if ($meta !== null) {
                $response['meta'] = $meta;
            }

            return response()->json($response, 200);
        }

        // Handle LengthAwarePaginator
        if ($paginator instanceof LengthAwarePaginator) {
            $response = [
                'success' => true,
                'message' => $message ?? 'Data retrieved successfully',
                'data' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ];

            if ($meta !== null) {
                $response['meta'] = $meta;
            }

            return response()->json($response, 200);
        }

        // Fallback for other types
        return $this->successResponse($paginator, $message);
    }

    /**
     * Return a created JSON response (201)
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    protected function createdResponse(
        $data = null,
        ?string $message = null
    ): JsonResponse {
        return $this->successResponse(
            $data,
            $message ?? 'Resource created successfully',
            201
        );
    }

    /**
     * Return an updated JSON response (200)
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    protected function updatedResponse(
        $data = null,
        ?string $message = null
    ): JsonResponse {
        return $this->successResponse(
            $data,
            $message ?? 'Resource updated successfully',
            200
        );
    }

    /**
     * Return a deleted JSON response (200)
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function deletedResponse(
        ?string $message = null
    ): JsonResponse {
        return $this->successResponse(
            null,
            $message ?? 'Resource deleted successfully',
            200
        );
    }

    /**
     * Return a no content JSON response (204)
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a conflict JSON response (409)
     *
     * @param string|null $message
     * @param mixed $data
     * @return JsonResponse
     */
    protected function conflictResponse(
        ?string $message = null,
        $data = null
    ): JsonResponse {
        return $this->errorResponse(
            $message ?? 'Conflict. The request could not be completed due to a conflict',
            409,
            null,
            $data
        );
    }

    /**
     * Return a too many requests JSON response (429)
     *
     * @param string|null $message
     * @param int|null $retryAfter
     * @return JsonResponse
     */
    protected function tooManyRequestsResponse(
        ?string $message = null,
        ?int $retryAfter = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message ?? 'Too many requests. Please try again later',
        ];

        if ($retryAfter !== null) {
            $response['retry_after'] = $retryAfter;
        }

        return response()->json($response, 429);
    }

    /**
     * Return a bad request JSON response (400)
     *
     * @param string|null $message
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function badRequestResponse(
        ?string $message = null,
        ?array $errors = null
    ): JsonResponse {
        return $this->errorResponse(
            $message ?? 'Bad request',
            400,
            $errors
        );
    }

    /**
     * Return a method not allowed JSON response (405)
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function methodNotAllowedResponse(
        ?string $message = null
    ): JsonResponse {
        return $this->errorResponse(
            $message ?? 'Method not allowed',
            405
        );
    }

    /**
     * Return a collection response with optional transformation
     *
     * @param Collection|array $collection
     * @param string|null $message
     * @param callable|null $transformer
     * @return JsonResponse
     */
    protected function collectionResponse(
        $collection,
        ?string $message = null,
        ?callable $transformer = null
    ): JsonResponse {
        $data = $collection;

        if ($transformer !== null && is_callable($transformer)) {
            if ($collection instanceof Collection) {
                $data = $collection->map($transformer)->values();
            } elseif (is_array($collection)) {
                $data = array_map($transformer, $collection);
            }
        }

        return $this->successResponse($data, $message);
    }
}

