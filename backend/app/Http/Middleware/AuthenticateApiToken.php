<?php

namespace App\Http\Middleware;

use App\Models\ApiAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();
        $token = $plainToken ? ApiAccessToken::with('user')->where('token_hash', hash('sha256', $plainToken))->first() : null;

        if (! $token || $token->user->role !== 'member' || ! $token->user->is_active || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json([
                'success' => false, 'data' => null, 'meta' => null,
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => '請先登入會員。'],
            ], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_access_token', $token);

        return $next($request);
    }
}
