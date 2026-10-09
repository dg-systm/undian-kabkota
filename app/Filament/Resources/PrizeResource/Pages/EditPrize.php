<?php

namespace App\Filament\Resources\PrizeResource\Pages;

use App\Filament\Resources\PrizeResource;
use App\Models\Prize;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPrize extends EditRecord
{
    protected static string $resource = PrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Prize $record, Actions\DeleteAction $action) {
                    if ($record->draws()->exists()) {
                        Notification::make()
                            ->title('Hadiah tidak dapat dihapus')
                            ->body("Hadiah \"{$record->name}\" sudah dipakai pada data pengundian. Hapus data pemenang terkait terlebih dahulu.")
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
