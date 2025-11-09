<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Only handle API requests
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions
     *
     * @param Request $request
     * @param Throwable $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException(Request $request, Throwable $e)
    {
        // Validation Exception (422)
        if ($e instanceof ValidationException) {
            return $this->validationErrorResponse($e);
        }

        // Model Not Found Exception (404)
        if ($e instanceof ModelNotFoundException) {
            return $this->modelNotFoundResponse($e);
        }

        // Authentication Exception (401)
        if ($e instanceof AuthenticationException) {
            return $this->authenticationErrorResponse();
        }

        // Authorization Exception (403)
        if ($e instanceof AuthorizationException) {
            return $this->authorizationErrorResponse($e);
        }

        // Not Found HTTP Exception (404)
        if ($e instanceof NotFoundHttpException) {
            return $this->notFoundHttpResponse();
        }

        // Method Not Allowed Exception (405)
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->methodNotAllowedResponse();
        }

        // HTTP Exception (with status code)
        if ($e instanceof HttpException) {
            return $this->httpExceptionResponse($e);
        }

        // Database Query Exception (500)
        if ($e instanceof QueryException) {
            return $this->queryExceptionResponse($e);
        }

        // General Exception (500)
        return $this->generalExceptionResponse($e);
    }

    /**
     * Return validation error response
     *
     * @param ValidationException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse(ValidationException $e)
    {
        $errors = $e->errors();
        $message = $e->getMessage() ?: 'Validation failed';

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Return model not found response
     *
     * @param ModelNotFoundException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function modelNotFoundResponse(ModelNotFoundException $e)
    {
        $model = class_basename($e->getModel());
        $message = "{$model} not found";

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Return authentication error response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function authenticationErrorResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please authenticate to access this resource',
        ], 401);
    }

    /**
     * Return authorization error response
     *
     * @param AuthorizationException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function authorizationErrorResponse(AuthorizationException $e)
    {
        $message = $e->getMessage() ?: 'Forbidden. You do not have permission to perform this action';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Return not found HTTP response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundHttpResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Route not found',
        ], 404);
    }

    /**
     * Return method not allowed response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function methodNotAllowedResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Method not allowed for this endpoint',
        ], 405);
    }

    /**
     * Return HTTP exception response
     *
     * @param HttpException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function httpExceptionResponse(HttpException $e)
    {
        $statusCode = $e->getStatusCode();
        $message = $e->getMessage() ?: 'An error occurred';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Return query exception response
     *
     * @param QueryException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function queryExceptionResponse(QueryException $e)
    {
        // Log the full exception for debugging
        Log::error('Database query exception: ' . $e->getMessage(), [
            'exception' => $e,
            'sql' => $e->getSql(),
            'bindings' => $e->getBindings(),
        ]);

        // Don't expose database errors in production
        $message = app()->environment('production')
            ? 'Database error occurred. Please try again later'
            : $e->getMessage();

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }

    /**
     * Return general exception response
     *
     * @param Throwable $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function generalExceptionResponse(Throwable $e)
    {
        // Log the exception
        Log::error('Unhandled exception: ' . $e->getMessage(), [
            'exception' => $e,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Don't expose internal errors in production
        $message = app()->environment('production')
            ? 'Internal server error. Please try again later'
            : $e->getMessage();

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }
}

