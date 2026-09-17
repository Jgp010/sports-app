<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $user = User::create([...$data, 'role' => 'member', 'is_active' => true]);

        return $this->tokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || $user->role !== 'member' || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false, 'data' => null, 'meta' => null,
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => '帳號或密碼錯誤。'],
            ], 422);
        }
        $user->forceFill(['last_login_at' => now()])->save();

        return $this->tokenResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_access_token')?->delete();

        return response()->json(['success' => true, 'data' => null, 'meta' => null, 'error' => null]);
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        $plainToken = Str::random(80);
        $expiresAt = now()->addDays(30);
        ApiAccessToken::create([
            'user_id' => $user->id, 'name' => 'android', 'token_hash' => hash('sha256', $plainToken), 'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['token' => $plainToken, 'token_type' => 'Bearer', 'expires_at' => $expiresAt->toIso8601String(), 'user' => MemberController::serializeUser($user)],
            'meta' => null, 'error' => null,
        ], $status);
    }
}
