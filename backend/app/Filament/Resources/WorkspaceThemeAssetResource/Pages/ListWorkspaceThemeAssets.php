<?php

namespace App\Filament\Resources\WorkspaceThemeAssetResource\Pages;

use App\Filament\Resources\WorkspaceThemeAssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkspaceThemeAssets extends ListRecords
{
    protected static string $resource = WorkspaceThemeAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
