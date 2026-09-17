<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SportController extends Controller
{
    public function index(): View
    {
        return view('admin.sports.index', ['sports' => Sport::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:sports,name'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        Sport::create([
            ...$data,
            'slug' => Str::slug($data['name']) ?: 'sport-'.now()->timestamp,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', '運動類別已新增。');
    }

    public function update(Request $request, Sport $sport): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:sports,name,'.$sport->id],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $sport->update([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', '運動類別已更新。');
    }
}
