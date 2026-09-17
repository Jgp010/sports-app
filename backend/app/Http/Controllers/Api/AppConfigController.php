<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'platform' => $request->string('platform', 'android')->toString(),
                'minimum_version' => '1.0.0',
                'latest_version' => '1.1.2',
                'force_update' => false,
                'privacy_url' => config('app.url').'/privacy',
            ],
            'meta' => null,
            'error' => null,
        ]);
    }
}
