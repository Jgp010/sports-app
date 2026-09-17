<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'counts' => NewsPost::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'recentPosts' => NewsPost::with('sport')->latest('updated_at')->limit(8)->get(),
            'systemCounts' => [
                'events' => Event::count(),
                'open_events' => Event::published()->get()->filter->isRegistrationOpen()->count(),
                'registrations' => EventRegistration::where('status', 'registered')->count(),
                'members' => User::where('role', 'member')->count(),
            ],
        ]);
    }
}
