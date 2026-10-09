<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrizeCategoryResource\Pages;
use App\Filament\Resources\PrizeCategoryResource\RelationManagers;
use App\Models\PrizeCategory;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class PrizeCategoryResource extends Resource
{
    protected static ?string $model = PrizeCategory::class;

    protected static ?string $navigationLabel = 'Kategori Hadiah'; // Label di sidebar

    protected static ?string $label = 'Kategori Hadiah'; // Judul halaman

    protected static ?string $pluralLabel = 'Kategori Hadiah';

    protected static ?string $pluralModelLabel = 'Kategori Hadiah';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('level')
                    ->label('Level')
                    ->numeric()
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama'),
                TextColumn::make('level')->label('Level')->sortable(),
                TextColumn::make('description')->label('Deskripsi'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (PrizeCategory $record, Tables\Actions\DeleteAction $action) {
                        if ($record->prizes()->exists()) {
                            Notification::make()
                                ->title('Kategori tidak dapat dihapus')
                                ->body("Kategori \"{$record->name}\" masih memiliki hadiah. Hapus atau pindahkan hadiah tersebut terlebih dahulu.")
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            $blocked = $records->filter(fn ($record) => $record->prizes()->exists());

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->title('Sebagian kategori tidak dapat dihapus')
                                    ->body('Kategori berikut masih memiliki hadiah: ' . $blocked->pluck('name')->implode(', ') . '. Hapus atau pindahkan hadiahnya terlebih dahulu.')
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrizeCategories::route('/'),
            'create' => Pages\CreatePrizeCategory::route('/create'),
            'edit' => Pages\EditPrizeCategory::route('/{record}/edit'),
        ];
    }
}
