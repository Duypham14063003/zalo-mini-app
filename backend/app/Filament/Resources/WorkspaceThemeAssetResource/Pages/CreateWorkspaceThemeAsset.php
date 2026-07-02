<?php

namespace App\Filament\Resources\WorkspaceThemeAssetResource\Pages;

use App\Filament\Resources\WorkspaceThemeAssetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkspaceThemeAsset extends CreateRecord
{
    protected static string $resource = WorkspaceThemeAssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['workspace_id'] = null;
        $data['mime_type'] = null;
        $data['source_kind'] = $data['source_kind'] ?? 'upload';

        return $data;
    }
}
