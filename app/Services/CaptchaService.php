<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CaptchaService
{
    public const SESSION_KEY = 'registration_captcha';

    public function regenerate(): string
    {
        $code = Str::upper(Str::random(5));
        // Avoid ambiguous characters.
        $code = str_replace(['0', 'O', '1', 'I', 'L'], ['2', 'A', '3', 'B', 'C'], $code);
        Session::put(self::SESSION_KEY, $code);

        return $code;
    }

    public function code(): string
    {
        if (! Session::has(self::SESSION_KEY)) {
            return $this->regenerate();
        }

        return (string) Session::get(self::SESSION_KEY);
    }

    public function matches(?string $input): bool
    {
        $expected = Session::get(self::SESSION_KEY);

        if (! is_string($expected) || $expected === '' || ! is_string($input)) {
            return false;
        }

        return hash_equals(Str::upper($expected), Str::upper(trim($input)));
    }

    public function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function svg(): string
    {
        $code = $this->code();
        $width = 160;
        $height = 48;
        $noise = '';

        for ($i = 0; $i < 6; $i++) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $x2 = random_int(0, $width);
            $y2 = random_int(0, $height);
            $noise .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#d4d4d4" stroke-width="1"/>',
                $x1,
                $y1,
                $x2,
                $y2
            );
        }

        $chars = '';
        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $x = 18 + ($i * 28);
            $y = random_int(30, 36);
            $rot = random_int(-18, 18);
            $chars .= sprintf(
                '<text x="%d" y="%d" transform="rotate(%d %d %d)" font-family="JetBrains Mono, IBM Plex Mono, monospace" font-size="22" font-weight="600" fill="#0a0a0a">%s</text>',
                $x,
                $y,
                $rot,
                $x,
                $y,
                htmlspecialchars($code[$i], ENT_QUOTES)
            );
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <rect width="100%" height="100%" fill="#f5f5f5"/>
  {$noise}
  {$chars}
</svg>
SVG;
    }
}
