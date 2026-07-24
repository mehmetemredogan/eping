<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PingResult;
use App\Models\PingTarget;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'targetCount' => PingTarget::count(),
            'activeTargetCount' => PingTarget::where('is_active', true)->count(),
            'resultCount' => PingResult::count(),
            'recentResults' => PingResult::with('target')->latest('tested_at')->limit(10)->get(),
            'categoryStats' => PingTarget::selectRaw('category, count(*) as total')
                ->groupBy('category')
                ->pluck('total', 'category'),
        ]);
    }
}
