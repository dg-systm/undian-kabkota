<?php

namespace App\Filament\Resources\PrizeCategoryResource\Pages;

use App\Filament\Resources\PrizeCategoryResource;
use App\Models\PrizeCategory;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPrizeCategory extends EditRecord
{
    protected static string $resource = PrizeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (PrizeCategory $record, Actions\DeleteAction $action) {
                    $total = $record->prizes()->count();

                    if ($total > 0) {
                        Notification::make()
                            ->title('Kategori tidak dapat dihapus')
                            ->body("Kategori \"{$record->name}\" masih memiliki {$total} hadiah. Hapus atau pindahkan hadiah tersebut terlebih dahulu.")
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
