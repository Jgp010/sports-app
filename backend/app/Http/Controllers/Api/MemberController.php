<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->response($request->user());
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'undisclosed'])],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
        $user->update($data);

        return $this->response($user->fresh());
    }

    private function response(User $user): JsonResponse
    {
        return response()->json(['success' => true, 'data' => self::serializeUser($user), 'meta' => null, 'error' => null]);
    }

    public static function serializeUser(User $user): array
    {
        return [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone,
            'birth_date' => $user->birth_date?->format('Y-m-d'), 'gender' => $user->gender, 'address' => $user->address,
            'sso_provider' => $user->sso_provider, 'has_sso_credential' => filled($user->sso_credential),
        ];
    }
}
