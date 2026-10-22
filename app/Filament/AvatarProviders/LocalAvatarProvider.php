<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

/**
 * Avatar lokal (offline) untuk panel admin.
 *
 * Menggantikan UiAvatarsProvider bawaan Filament yang mengambil gambar dari
 * https://ui-avatars.com. Dengan provider ini panel tidak lagi bergantung pada
 * koneksi internet, sehingga halaman admin tetap cepat saat jaringan mati.
 */
class LocalAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        return asset('images/avatar-default.svg');
    }
}
