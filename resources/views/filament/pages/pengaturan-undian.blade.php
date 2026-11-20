<x-filament-panels::page>
    <div class="space-y-4">

        @php
            $mode = $this->data['draw_mode'] ?? '';
            $modeBadge = match ($mode) {
                \App\Support\UndianSettings::MODE_KECAMATAN => ['Undian Per Kecamatan', 'success'],
                \App\Support\UndianSettings::MODE_GLOBAL6   => ['Global Semua Kecamatan', 'warning'],
                default                                     => ['Belum Diatur', 'danger'],
            };
        @endphp

        {{-- Ringkasan status saat ini --}}
        <x-filament::section>
            <x-slot name="heading">
                <span class="inline-flex items-center gap-2">
                    Status Saat Ini
                    <x-filament::badge :color="$modeBadge[1]">
                        {{ $modeBadge[0] }}
                    </x-filament::badge>
                </span>
            </x-slot>
            <x-slot name="description">
                Mode pengundian wajib diatur admin terlebih dahulu sebelum halaman undian dapat digunakan.
            </x-slot>
        </x-filament::section>

        {{-- Panduan singkat --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    <span class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary-500 text-xs text-white">1</span>
                    Pilih Mode Pengundian
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Per Kecamatan (biasa + Grand Prize) atau Global Semua Kecamatan (komposisi pemenang).
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    <span class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary-500 text-xs text-white">2</span>
                    Atur Pengaturan Lanjutan
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Mode Per Kecamatan: di sidebar halaman undian. Mode Global: komposisi roda di halaman ini, jumlah pemenang di sidebar undian.
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    <span class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary-500 text-xs text-white">3</span>
                    Buka Halaman Undian
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Pengaturan wilayah (Kabupaten/Kota &amp; ID Lokasi) diatur pada file <code>.env</code>.
                </p>
            </div>
        </div>

        {{-- Form pengaturan --}}
        <form wire:submit="save" class="space-y-4">
            {{ $this->form }}

            <div class="fi-form-actions">
                <div class="flex flex-wrap items-center justify-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <x-filament::button
                        type="submit"
                        color="primary"
                        icon="heroicon-m-check-circle"
                    >
                        Simpan Pengaturan
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        color="gray"
                        icon="heroicon-m-arrow-uturn-left"
                        href="{{ url('/') }}"
                        target="_blank"
                    >
                        Buka Halaman Undian
                    </x-filament::button>
                </div>
            </div>
        </form>

    </div>
</x-filament-panels::page>
