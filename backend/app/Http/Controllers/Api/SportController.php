<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Http\JsonResponse;

class SportController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Sport::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug']),
            'meta' => null,
            'error' => null,
        ]);
    }
}
