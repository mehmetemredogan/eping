<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(CaptchaService $captcha): View
    {
        $captcha->regenerate();

        return view('auth.register');
    }

    public function store(Request $request, CaptchaService $captcha): RedirectResponse
    {
        $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:32',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:'.User::class,
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'captcha' => ['required', 'string'],
        ], [
            'username.regex' => __('ping.username_format'),
            'captcha.required' => __('ping.captcha_required'),
        ]);

        if (! $captcha->matches($request->string('captcha')->toString())) {
            $captcha->regenerate();

            throw ValidationException::withMessages([
                'captcha' => __('ping.captcha_invalid'),
            ]);
        }

        $captcha->forget();

        $user = User::create([
            'username' => strtolower($request->string('username')->toString()),
            'password' => $request->string('password')->toString(),
            'is_admin' => false,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('history.index');
    }
}
