<?php

namespace App\Filament\Resources\TrapResource\Pages;

use App\Filament\Resources\TrapResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrap extends EditRecord
{
    protected static string $resource = TrapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
