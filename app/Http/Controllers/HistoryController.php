<?php

namespace App\Http\Controllers;

use App\Models\PingResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->string('date')->toString();
        $userId = $request->user()->id;

        $query = PingResult::query()
            ->with('target')
            ->where('user_id', $userId)
            ->latest('tested_at');

        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $query->whereDate('tested_at', $date);
        }

        $results = $query->limit(500)->get();

        $groupedByDate = $results->groupBy(function (PingResult $result) {
            return $result->tested_at?->format('Y-m-d') ?? 'unknown';
        });

        $availableDates = PingResult::query()
            ->where('user_id', $userId)
            ->whereNotNull('tested_at')
            ->select(DB::raw('DATE(tested_at) as day'))
            ->groupBy(DB::raw('DATE(tested_at)'))
            ->orderByDesc('day')
            ->limit(60)
            ->pluck('day')
            ->map(fn ($day) => (string) $day);

        return view('ping.history', [
            'groupedByDate' => $groupedByDate,
            'availableDates' => $availableDates,
            'selectedDate' => $date,
        ]);
    }
}
