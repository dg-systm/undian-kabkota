<?php

namespace App\Filament\Resources\DrawResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WinnersRelationManager extends RelationManager
{
    protected static string $relationship = 'winners';

    protected function canDelete(Model $record): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('id_kendaraan')
                    ->label('ID Kendaraan')
                    ->content(fn($record) => $record->kendaraan->id_kendaraan),
                Placeholder::make('kendaraan.no_polisi')
                    ->label('Nomor Polisi')
                    ->content(fn($record) => $record->kendaraan->masked_no_polisi),
                Placeholder::make('kendaraan.nama')
                    ->label('Nama')
                    ->content(fn($record) => $record->kendaraan->nama),
                Placeholder::make('kendaraan.alamat')
                    ->label('Alamat')
                    ->content(fn($record) => $record->kendaraan->alamat),
                Placeholder::make('kendaraan.lokasi')
                    ->label('Lokasi')
                    ->content(fn($record) => $record->kendaraan->lokasi),
                Placeholder::make('kendaraan.roda')
                    ->label('Roda')
                    ->content(fn($record) => $record->kendaraan->roda),
                Placeholder::make('kendaraan.warna_tnkb')
                    ->label('Warna Tnkb')
                    ->content(fn($record) => $record->kendaraan->warna_tnkb),
                Placeholder::make('kendaraan.fungsi_kend')
                    ->label('Fungsi Kend')
                    ->content(fn($record) => $record->kendaraan->fungsi_kend),
                Placeholder::make('kendaraan.id_billing')
                    ->label('ID Billing')
                    ->content(fn($record) => $record->kendaraan->id_billing),
                Placeholder::make('kendaraan.tgl_bayar')
                    ->label('Tgl Bayar')
                    ->content(fn($record) => $record->kendaraan->tgl_bayar),
                Placeholder::make('prize.name')
                    ->label('Hadiah')
                    ->content(fn($record) => $record->prize?->name ?? '-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('winners')
            ->columns([
                TextColumn::make('kendaraan.masked_no_polisi')->label('Nomor Polisi'),
                TextColumn::make('kendaraan.roda')->label('Roda'),
                TextColumn::make('kendaraan.nama')->label('Nama Pemilik'),
                TextColumn::make('kendaraan.lokasi')->label('Samsat'),
                TextColumn::make('kendaraan.alamat')->label('Alamat'),
                TextColumn::make('prize.name')->label('Hadiah')->default('-'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
