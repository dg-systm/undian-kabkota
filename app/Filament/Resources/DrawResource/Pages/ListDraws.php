<?php

namespace App\Filament\Resources\DrawResource\Pages;

use App\Filament\Resources\DrawResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDraws extends ListRecords
{
    protected static string $resource = DrawResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print_all')
                ->label('Cetak Semua Pemenang (PDF)')
                ->url(route('download.pdf.all-winners'))
                ->openUrlInNewTab()
                ->icon('heroicon-o-printer')
                ->color('success'),
            Actions\CreateAction::make(),
        ];
    }
}
