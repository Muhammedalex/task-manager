<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Access token expiration time in minutes
     */
    private const ACCESS_TOKEN_EXPIRATION = 60; // 1 hour

    /**
     * Refresh token expiration time in days
     */
    private const REFRESH_TOKEN_EXPIRATION_DAYS = 30; // 30 days

    /**
     * User login
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->unauthorizedResponse('Invalid email or password.');
            }

            $accessToken = $user->createToken(
                'access-token',
                ['*'],
                now()->addMinutes(self::ACCESS_TOKEN_EXPIRATION)
            )->plainTextToken;

            $refreshTokenPlain = RefreshToken::generateToken();
            $refreshTokenHash = hash('sha256', $refreshTokenPlain);

            RefreshToken::create([
                'user_id' => $user->id,
                'token' => $refreshTokenHash,
                'device_name' => $request->header('User-Agent', 'Unknown Device'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'expires_at' => now()->addDays(self::REFRESH_TOKEN_EXPIRATION_DAYS),
            ]);

            $user->load('roles', 'permissions');

            $cookie = Cookie::make(
                'refresh_token',
                $refreshTokenPlain,
                self::REFRESH_TOKEN_EXPIRATION_DAYS * 24 * 60,
                '/',
                null,
                true,
                true,
                false,
                'strict'
            );

            return $this->successResponse([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => self::ACCESS_TOKEN_EXPIRATION * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name') ?? [],
                    'permissions' => $user->getAllPermissions()->pluck('name') ?? [],
                ],
            ], 'Login successful')->cookie($cookie);
        } catch (Throwable $e) {
            Log::error('Login error', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return $this->serverErrorResponse('An unexpected error occurred during login. Please try again later.');
        }
    }

    /**
     * Refresh access token using refresh token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $refreshTokenPlain = $request->cookie('refresh_token') ?? $request->input('refresh_token');

            if (!$refreshTokenPlain) {
                return $this->unauthorizedResponse('Refresh token not found. Please login again.');
            }

            $refreshTokenHash = hash('sha256', $refreshTokenPlain);
            $refreshToken = RefreshToken::where('token', $refreshTokenHash)
                ->where('is_revoked', false)
                ->first();

            if (!$refreshToken || !$refreshToken->isValid()) {
                return $this->unauthorizedResponse('Invalid or expired refresh token. Please login again.');
            }

            $user = $refreshToken->user;

            if (!$user) {
                $refreshToken->revoke();
                return $this->unauthorizedResponse('Invalid refresh token. Please login again.');
            }

            $user->tokens()->delete();

            $accessToken = $user->createToken(
                'access-token',
                ['*'],
                now()->addMinutes(self::ACCESS_TOKEN_EXPIRATION)
            )->plainTextToken;

            $refreshToken->updateLastUsed();
            $user->load('roles', 'permissions');

            return $this->successResponse([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => self::ACCESS_TOKEN_EXPIRATION * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name') ?? [],
                    'permissions' => $user->getAllPermissions()->pluck('name') ?? [],
                ],
            ], 'Token refreshed successfully');
        } catch (Throwable $e) {
            Log::error('Refresh token error', [
                'error' => $e->getMessage(),
            ]);

            return $this->serverErrorResponse('An unexpected error occurred while refreshing token. Please try again later.');
        }
    }

    /**
     * User logout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user) {
                $user->tokens()->delete();

                $refreshTokenPlain = $request->cookie('refresh_token');
                if ($refreshTokenPlain) {
                    $refreshTokenHash = hash('sha256', $refreshTokenPlain);
                    $refreshToken = RefreshToken::where('token', $refreshTokenHash)
                        ->where('user_id', $user->id)
                        ->first();

                    if ($refreshToken) {
                        $refreshToken->revoke();
                    }
                }
            }

            $cookie = Cookie::forget('refresh_token');

            return $this->successResponse(null, 'Logged out successfully')
                ->cookie($cookie);
        } catch (Throwable $e) {
            Log::error('Logout error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverErrorResponse('An unexpected error occurred during logout. Please try again.');
        }
    }

    /**
     * Logout from all devices
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->unauthorizedResponse('User not authenticated.');
            }

            $user->tokens()->delete();
            RefreshToken::where('user_id', $user->id)->update(['is_revoked' => true]);

            $cookie = Cookie::forget('refresh_token');

            return $this->successResponse(null, 'Logged out from all devices successfully')
                ->cookie($cookie);
        } catch (Throwable $e) {
            Log::error('Logout all error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverErrorResponse('An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * Get authenticated user details
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->unauthorizedResponse('User not authenticated.');
            }

            $user->load('roles', 'permissions');

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'roles' => $user->roles->pluck('name') ?? [],
                    'permissions' => $user->getAllPermissions()->pluck('name') ?? [],
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ], 'User data retrieved successfully');
        } catch (Throwable $e) {
            Log::error('Get user data error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverErrorResponse('An unexpected error occurred while retrieving user data. Please try again later.');
        }
    }
}
