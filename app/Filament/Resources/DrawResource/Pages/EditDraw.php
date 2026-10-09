<?php

namespace App\Filament\Resources\DrawResource\Pages;

use App\Filament\Resources\DrawResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDraw extends EditRecord
{
    protected static string $resource = DrawResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('print')
                ->label('Cetak Pdf')
                ->url(route('download.pdf.draw', ['id' => $this->record->id]))
                ->openUrlInNewTab()
                ->icon('heroicon-o-printer')
                ->color('success'),
        ];
    }
}
