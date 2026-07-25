<?php

namespace App\Http\Controllers;

use App\Models\PingResult;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $historyCount = PingResult::query()
            ->where('user_id', $request->user()->id)
            ->count();

        return view('ping.settings', [
            'user' => $request->user(),
            'historyCount' => $historyCount,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:32',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ], [
            'username.regex' => __('ping.username_format'),
        ]);

        $user->update([
            'username' => strtolower($validated['username']),
        ]);

        return redirect()
            ->route('settings.edit')
            ->with('success', __('ping.profile_updated'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => __('ping.password_current_invalid'),
        ]);

        $user = $request->user();
        $user->update([
            'password' => $validated['password'],
        ]);

        // Invalidate API tokens so desktop clients must sign in again.
        $user->tokens()->delete();
        $request->session()->regenerate();

        return redirect()
            ->route('settings.edit')
            ->with('success', __('ping.password_updated'));
    }

    public function clearHistory(Request $request): RedirectResponse
    {
        $deleted = PingResult::query()
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect()
            ->route('settings.edit')
            ->with('success', __('ping.history_cleared', ['count' => $deleted]));
    }
}
