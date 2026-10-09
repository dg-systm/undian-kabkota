<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DrawResource\Pages;
use App\Filament\Resources\DrawResource\RelationManagers;
use App\Filament\Resources\DrawResource\RelationManagers\WinnersRelationManager;
use App\Models\Draw;
use App\Models\Prize;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DrawResource extends Resource
{
    protected static ?string $model = Draw::class;

    protected static ?string $navigationLabel = 'Pemenang'; // Label di sidebar

    protected static ?string $label = 'Pemenang'; // Judul halaman

    protected static ?string $pluralLabel = 'Pemenang';

    protected static ?string $pluralModelLabel = 'Pemenang';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('prize_id')
                    ->label('Hadiah')
                    ->options(Prize::pluck('name', 'id'))
                    ->disabled()
                    ->placeholder('Hadiah Campuran'),
                TextInput::make('quantity')
                    ->label('Jumlah Pemenang')
                    ->disabled()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('prize.name')
                    ->label('Hadiah')
                    ->default('Hadiah Campuran'),
                TextColumn::make('prize_category.name')
                    ->label('Kategori Hadiah')
                    ->default('-')
                    ->sortable(),
                TextColumn::make('quantity')->label('Jumlah Pemenang')->sortable(),
                TextColumn::make('created_at')->label('Tanggal')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            WinnersRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDraws::route('/'),
            'create' => Pages\CreateDraw::route('/create'),
            'edit' => Pages\EditDraw::route('/{record}/edit'),
        ];
    }
}
