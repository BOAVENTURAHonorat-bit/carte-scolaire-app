<?php

namespace App\Support;

use Illuminate\Support\Str;

class FlagResolver
{
    /**
     * Resolve a country name to the base64 data URI of its bundled flag asset.
     * Flags live in public/images/flags/{slug}.png.
     */
    public static function uri(?string $country): ?string
    {
        if (! $country) {
            return null;
        }

        $path = public_path('images/flags/'.Str::slug($country).'.png');

        if (! file_exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }
}
