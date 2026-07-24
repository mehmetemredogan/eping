<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PingResultController;
use App\Http\Controllers\Admin\PingTargetController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

// No public homepage / browser-based ping tool: the web app only serves
// auth, the member panel (ping history submitted by the terminal client),
// and the admin panel. `/` just routes visitors to the right place.
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'history.index' : 'login');
})->name('home');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
Route::get('/captcha', [CaptchaController::class, 'image'])->middleware('throttle:30,1')->name('captcha.image');

Route::middleware('auth')->group(function () {
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('targets', PingTargetController::class)->except(['show']);
    Route::resource('providers', ProviderController::class)->only(['index', 'edit', 'update']);
    Route::get('results', [PingResultController::class, 'index'])->name('results.index');
    Route::get('results/{result}', [PingResultController::class, 'show'])->name('results.show');
});

require __DIR__.'/auth.php';
