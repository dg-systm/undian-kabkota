<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrizeResource\Pages;
use App\Filament\Resources\PrizeResource\RelationManagers;
use App\Models\Prize;
use App\Models\PrizeCategory;
use Filament\Forms;
use Filament\Forms\Components\Select;
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

class PrizeResource extends Resource
{
    protected static ?string $model = Prize::class;

    protected static ?string $navigationLabel = 'Hadiah'; // Label di sidebar

    protected static ?string $label = 'Hadiah'; // Judul halaman

    protected static ?string $pluralLabel = 'Hadiah';

    protected static ?string $pluralModelLabel = 'Hadiah';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('prize_category_id')
                    ->label('Kategori Hadiah')
                    ->options(PrizeCategory::pluck('name', 'id'))
                    ->required(),
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi'),
                TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama'),
                TextColumn::make('description')->label('Deskripsi'),
                TextColumn::make('quantity')->label('Jumlah')->sortable(),
                TextColumn::make('prize_category.name')->label('Kategori Hadiah')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Prize $record, Tables\Actions\DeleteAction $action) {
                        if ($record->draws()->exists()) {
                            Notification::make()
                                ->title('Hadiah tidak dapat dihapus')
                                ->body("Hadiah \"{$record->name}\" sudah dipakai pada data pengundian. Hapus data pemenang terkait terlebih dahulu.")
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
                            $blocked = $records->filter(fn ($record) => $record->draws()->exists());

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->title('Sebagian hadiah tidak dapat dihapus')
                                    ->body('Hadiah berikut sudah dipakai pada data pengundian: ' . $blocked->pluck('name')->implode(', ') . '.')
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
            'index' => Pages\ListPrizes::route('/'),
            'create' => Pages\CreatePrize::route('/create'),
            'edit' => Pages\EditPrize::route('/{record}/edit'),
        ];
    }
}
