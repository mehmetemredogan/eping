<?php

namespace App\Http\Controllers;

use App\Services\CaptchaService;
use Symfony\Component\HttpFoundation\Response;

class CaptchaController extends Controller
{
    public function image(CaptchaService $captcha): Response
    {
        $captcha->regenerate();

        return response($captcha->svg(), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
