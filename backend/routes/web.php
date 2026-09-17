<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NewsPostController;
use App\Http\Controllers\Admin\SportController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::view('/privacy', 'privacy')->name('privacy');
Route::get('/storage/news-covers/{filename}', [MediaController::class, 'newsCover'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.news-cover');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'store'])->name('admin.login.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::resource('accounts', AccountController::class)->except(['show']);
    Route::resource('news', NewsPostController::class)->parameters(['news' => 'newsPost'])->except(['show']);
    Route::resource('events', EventController::class)->except(['show']);
    Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/registrations/{registration}/edit', [RegistrationController::class, 'edit'])->name('registrations.edit');
    Route::put('/registrations/{registration}', [RegistrationController::class, 'update'])->name('registrations.update');
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::get('/sports', [SportController::class, 'index'])->name('sports.index');
    Route::post('/sports', [SportController::class, 'store'])->name('sports.store');
    Route::put('/sports/{sport}', [SportController::class, 'update'])->name('sports.update');
});
