<?php

namespace App\Filament\Widgets;

use App\Models\PrizeCategory;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RekapHadiahWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Total Hadiah per Kategori';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PrizeCategory::query()
                    ->withSum('prizes', 'quantity')
                    ->orderBy('level')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kategori Hadiah')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('prizes_sum_quantity')
                    ->label('Total Hadiah')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Stok Tersedia')
                    ->alignCenter()
                    ->badge()
                    ->state(fn (PrizeCategory $record): int => (int) $record->prizes
                        ->sum(fn ($prize) => $prize->available_quantity))
                    ->color(fn ($state): string => ((int) $state) > 0 ? 'success' : 'danger'),
            ])
            ->paginated(false);
    }
}
