<?php

namespace App\Filament\Pages;

use App\Support\UndianSettings;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Halaman admin "Pengaturan Undian".
 *
 * Konfigurasi undian aplikasi diatur langsung di sini (seperti halaman
 * pengaturan biasa) dan disimpan pada file JSON, sehingga database tidak
 * perlu objek baru (database hanya menyediakan SP getWinner).
 *
 * Admin WAJIB memilih mode pengundian terlebih dahulu:
 * - Per Kecamatan    : tidak ada pengaturan lanjutan; sisanya diatur
 *                      di sidebar halaman undian (kecamatan, hadiah,
 *                      jumlah pemenang per kecamatan).
 * - Global Komposisi : komposisi minimal pemenang roda 2 & roda 4 diatur
 *                      di halaman ini. Jumlah pemenang total diatur di
 *                      sidebar halaman Grand Prize; sisa di atas komposisi
 *                      diambil dari semua kendaraan.
 *
 * Wilayah (Kabupaten/Kota & ID Lokasi) diatur langsung pada file .env.
 */
class PengaturanUndian extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Undian';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament.pages.pengaturan-undian';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = UndianSettings::all();

        $this->form->fill([
            'draw_mode'    => $settings['draw_mode'],
            'global_roda2' => $settings['global_roda2'],
            'global_roda4' => $settings['global_roda4'],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->settingsSchema())
            ->statePath('data');
    }

    protected function settingsSchema(): array
    {
        return [
            Section::make('Mode Pengundian')
                ->icon('heroicon-m-adjustments-horizontal')
                ->description('Wajib dipilih terlebih dahulu. Selama mode belum di-set, halaman undian tidak dapat mengundi.')
                ->columns(2)
                ->schema([
                    Select::make('draw_mode')
                        ->label('Mode Pengundian')
                        ->options([
                            UndianSettings::MODE_KECAMATAN => 'Undian Per Kecamatan (biasa + Grand Prize)',
                            UndianSettings::MODE_GLOBAL6   => 'Global Semua Kecamatan (Komposisi Pemenang)',
                        ])
                        ->placeholder('-- Pilih Mode Pengundian --')
                        ->columnSpan(1)
                        ->required()
                        ->live(),
                    Placeholder::make('mode_info')
                        ->label('Keterangan')
                        ->content(function ($get) {
                            return match ($get('draw_mode')) {
                                UndianSettings::MODE_KECAMATAN =>
                                    'Halaman undian: Undian Biasa (per kecamatan) dan Grand Prize (global fleksibel). Pengaturan lanjutan ada di sidebar halaman undian.',
                                UndianSettings::MODE_GLOBAL6 =>
                                    'Halaman undian: hanya Grand Prize (semua kecamatan). Komposisi minimal roda diatur di bawah, jumlah pemenang total di sidebar halaman undian.',
                                default =>
                                    'Belum ada mode aktif. Halaman undian baru bisa digunakan setelah mode disimpan.',
                            };
                        })
                        ->columnSpan(1),
                ]),

            Section::make('Komposisi Pemenang (Mode Global)')
                ->icon('heroicon-m-truck')
                ->visible(fn ($get) => $get('draw_mode') === UndianSettings::MODE_GLOBAL6)
                ->columns(3)
                ->schema([
                    TextInput::make('global_roda2')
                        ->label('Pemenang Roda 2')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(99)
                        ->default(5)
                        ->live()
                        ->required(),
                    TextInput::make('global_roda4')
                        ->label('Pemenang Roda 4')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(99)
                        ->default(1)
                        ->live()
                        ->required(),
                    Placeholder::make('total_pemenang')
                        ->label('Total Pemenang')
                        ->content(function ($get) {
                            $total = (int) $get('global_roda2') + (int) $get('global_roda4');

                            return $total > 0
                                ? $total . ' pemenang per pengundian'
                                : 'Minimal 1 pemenang (jumlahkan roda 2 dan/atau roda 4).';
                        }),
                ]),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $saved = UndianSettings::save($data);

        $modeLabel = match ($saved['draw_mode']) {
            UndianSettings::MODE_KECAMATAN => 'Per Kecamatan',
            UndianSettings::MODE_GLOBAL6   => 'Global Semua Kecamatan — '
                . $saved['global_roda2'] . ' roda 2 + ' . $saved['global_roda4'] . ' roda 4 = '
                . ($saved['global_roda2'] + $saved['global_roda4']) . ' pemenang',
            default                        => 'belum di-set',
        };

        Notification::make()
            ->success()
            ->title('Pengaturan undian tersimpan')
            ->body('Mode: ' . $modeLabel)
            ->send();
    }
}
