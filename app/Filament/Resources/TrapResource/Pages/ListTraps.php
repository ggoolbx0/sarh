<?php

namespace App\Filament\Resources\TrapResource\Pages;

use App\Filament\Resources\TrapResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTraps extends ListRecords
{
    protected static string $resource = TrapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
