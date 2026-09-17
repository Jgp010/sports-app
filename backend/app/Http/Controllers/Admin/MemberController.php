<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $members = User::where('role', 'member')->withCount('eventRegistrations')
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = '%'.$request->string('q').'%';
                $q->where(fn ($sub) => $sub->where('name', 'like', $keyword)->orWhere('email', 'like', $keyword)->orWhere('phone', 'like', $keyword));
            })->latest()->paginate(20)->withQueryString();

        return view('admin.members.index', compact('members'));
    }

    public function edit(User $member): View
    {
        abort_unless($member->role === 'member', 404);
        return view('admin.members.edit', ['member' => $member->load(['eventRegistrations.event'])]);
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        abort_unless($member->role === 'member', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($member->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'undisclosed'])],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sso_provider' => ['nullable', 'string', 'max:60'],
            'sso_subject' => ['nullable', 'string', 'max:191'],
            'sso_credential' => ['nullable', 'string', 'max:10000'],
            'password' => ['nullable', Password::min(8)],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['role'] = 'member';
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        if (blank($data['sso_credential'] ?? null)) {
            unset($data['sso_credential']);
        }
        $member->update($data);

        return back()->with('success', '會員資料已更新。');
    }
}
