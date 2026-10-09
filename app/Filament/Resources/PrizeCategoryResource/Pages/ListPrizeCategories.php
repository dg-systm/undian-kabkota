<?php

namespace App\Filament\Resources\PrizeCategoryResource\Pages;

use App\Filament\Resources\PrizeCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrizeCategories extends ListRecords
{
    protected static string $resource = PrizeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
