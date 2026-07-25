<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PingResultController;
use App\Http\Controllers\Admin\PingTargetController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

// No browser-based ping tool. Public surface: anonymized ISP stats.
// Authenticated users land on their member panel.
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'history.index' : 'stats.index');
})->name('home');

Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
Route::get('/captcha', [CaptchaController::class, 'image'])->middleware('throttle:30,1')->name('captcha.image');

Route::middleware('auth')->group(function () {
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::delete('/settings/history', [SettingsController::class, 'clearHistory'])->name('settings.history');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('targets', PingTargetController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('providers', ProviderController::class)->except(['show']);
    Route::get('results', [PingResultController::class, 'index'])->name('results.index');
    Route::get('results/{result}', [PingResultController::class, 'show'])->name('results.show');
});

require __DIR__.'/auth.php';
