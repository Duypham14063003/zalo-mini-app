<?php

namespace App\Filament\Resources\WorkspaceThemeAssetResource\Pages;

use App\Filament\Resources\WorkspaceThemeAssetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkspaceThemeAsset extends EditRecord
{
    protected static string $resource = WorkspaceThemeAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['workspace_id'] = null;
        $data['mime_type'] = null;

        return $data;
    }
}
