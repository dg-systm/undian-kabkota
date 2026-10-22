<?php

namespace App\Filament\FontProviders;

use Filament\FontProviders\Contracts\FontProvider;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * Font provider lokal untuk panel admin.
 *
 * Menggantikan BunnyFontProvider bawaan Filament yang memuat font dari
 * https://fonts.bunny.net. File font Inter sudah tersedia di
 * public/codebase/fonts/inter, jadi panel tetap tampil normal tanpa internet.
 */
class LocalFontProvider implements FontProvider
{
    public function getHtml(string $family, ?string $url = null): Htmlable
    {
        return new HtmlString(
            '<link href="' . e(asset('css/filament-local-font.css')) . '" rel="stylesheet" />'
        );
    }
}
