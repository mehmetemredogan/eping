<?php

namespace App\Http\Controllers;

use App\Services\IspStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function index(Request $request, IspStatsService $stats): View
    {
        $filters = [
            'isp' => (string) $request->string('isp'),
            'provider' => (string) $request->string('provider'),
            'country' => (string) $request->string('country'),
            'min_samples' => max(1, min(50, (int) $request->input('min_samples', IspStatsService::DEFAULT_MIN_SAMPLES))),
        ];

        return view('ping.stats', [
            'rows' => $stats->aggregate($filters),
            'filters' => $filters,
            'isps' => $stats->availableIsps(),
            'providers' => $stats->availableProviders(),
            'countries' => $stats->availableCountries(),
            'minSamplesDefault' => IspStatsService::DEFAULT_MIN_SAMPLES,
        ]);
    }
}
