<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string'],
        ]);
        $key = strtolower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['username' => '登入嘗試過多，請稍後再試。'])->onlyInput('username');
        }

        if (! Auth::attempt([...$credentials, 'is_active' => true])) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['username' => '帳號或密碼錯誤。'])->onlyInput('username');
        }

        if (! $request->user()->isAdmin()) {
            Auth::logout();
            return back()->withErrors(['username' => '此帳號沒有後台權限。']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        AdminAuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'login',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AdminAuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'logout',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
