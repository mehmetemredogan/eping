<?php

namespace App\Http\Controllers;

use App\Models\NetworkQualityTest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NetworkQualityController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()?->id;

        $userTests = null;
        if ($userId) {
            $userTests = NetworkQualityTest::query()
                ->where('user_id', $userId)
                ->latest('tested_at')
                ->limit(20)
                ->get();
        }

        $latestTest = NetworkQualityTest::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->latest('tested_at')
            ->first();

        // Global recent quality overview (anonymized)
        $recentPublicTests = NetworkQualityTest::query()
            ->latest('tested_at')
            ->limit(10)
            ->get();

        return view('quality.index', [
            'latestTest' => $latestTest,
            'userTests' => $userTests,
            'recentPublicTests' => $recentPublicTests,
        ]);
    }

    public function show(Request $request, NetworkQualityTest $test): View
    {
        return view('quality.show', [
            'test' => $test,
        ]);
    }
}
