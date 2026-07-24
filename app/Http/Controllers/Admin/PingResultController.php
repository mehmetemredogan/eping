<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PingResult;
use App\Models\PingTarget;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PingResultController extends Controller
{
    public function index(Request $request): View
    {
        $results = PingResult::query()
            ->with('target')
            ->when($request->filled('target_id'), fn ($q) => $q->where('ping_target_id', $request->integer('target_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('session_id'), fn ($q) => $q->where('session_id', $request->string('session_id')))
            ->latest('tested_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.results.index', [
            'results' => $results,
            'targets' => PingTarget::orderBy('name')->get(),
        ]);
    }

    public function show(PingResult $result): View
    {
        $result->load('target');

        return view('admin.results.show', [
            'result' => $result,
        ]);
    }
}
