<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdministrator($request);
        $accounts = User::whereIn('role', ['admin', 'editor'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('q').'%';
                $query->where(fn ($sub) => $sub->where('username', 'like', $keyword)->orWhere('name', 'like', $keyword));
            })
            ->orderBy('username')->paginate(20)->withQueryString();

        return view('admin.accounts.index', compact('accounts'));
    }

    public function create(Request $request): View
    {
        $this->authorizeAdministrator($request);
        return view('admin.accounts.form', ['account' => new User()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator($request);
        $data = $this->validated($request);
        User::create([...$data, 'email' => null]);

        return redirect()->route('admin.accounts.index')->with('success', '後台帳號已新增。');
    }

    public function edit(Request $request, User $account): View
    {
        $this->authorizeAdministrator($request);
        $this->ensureBackendAccount($account);
        return view('admin.accounts.form', compact('account'));
    }

    public function update(Request $request, User $account): RedirectResponse
    {
        $this->authorizeAdministrator($request);
        $this->ensureBackendAccount($account);
        $data = $this->validated($request, $account);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        if ($account->is($request->user())) {
            $data['role'] = 'admin';
            $data['is_active'] = true;
        }
        $account->update([...$data, 'email' => null]);

        return redirect()->route('admin.accounts.index')->with('success', '後台帳號已更新。');
    }

    public function destroy(Request $request, User $account): RedirectResponse
    {
        $this->authorizeAdministrator($request);
        $this->ensureBackendAccount($account);
        abort_if($account->is($request->user()), 422, '無法刪除目前登入中的帳號。');
        if ($this->hasManagedContent($account)) {
            return back()->withErrors(['account' => '此帳號已有內容或賽事操作紀錄，請改為停用帳號，以保留資料關聯。']);
        }
        $account->delete();

        return redirect()->route('admin.accounts.index')->with('success', '後台帳號已刪除。');
    }

    private function validated(Request $request, ?User $account = null): array
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:60', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($account?->id)],
            'name' => ['required', 'string', 'max:80'],
            'role' => ['required', Rule::in(['admin', 'editor'])],
            'is_active' => ['nullable', 'boolean'],
            'password' => [$account ? 'nullable' : 'required', Password::min(8)],
        ], [
            'username.alpha_dash' => '帳號只能使用英文字母、數字、破折號與底線。',
            'username.unique' => '此帳號已被使用。',
            'password.required' => '新增帳號時必須設定密碼。',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function authorizeAdministrator(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()->is_active, 403);
    }

    private function ensureBackendAccount(User $account): void
    {
        abort_unless(in_array($account->role, ['admin', 'editor'], true), 404);
    }

    private function hasManagedContent(User $account): bool
    {
        return DB::table('news_posts')->where('created_by', $account->id)->orWhere('updated_by', $account->id)->exists()
            || DB::table('events')->where('created_by', $account->id)->orWhere('updated_by', $account->id)->exists();
    }
}
