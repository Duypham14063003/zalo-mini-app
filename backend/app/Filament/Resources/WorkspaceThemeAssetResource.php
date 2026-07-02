<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkspaceThemeAssetResource\Pages\CreateWorkspaceThemeAsset;
use App\Filament\Resources\WorkspaceThemeAssetResource\Pages\EditWorkspaceThemeAsset;
use App\Filament\Resources\WorkspaceThemeAssetResource\Pages\ListWorkspaceThemeAssets;
use App\Models\WorkspaceThemeAsset;
use App\Services\WorkspaceThemeAssetService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class WorkspaceThemeAssetResource extends Resource
{
    protected static ?string $model = WorkspaceThemeAsset::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Thư viện theme';

    protected static ?string $modelLabel = 'asset theme';

    protected static ?string $pluralModelLabel = 'asset theme';

    public static function form(Schema $schema): Schema
    {
        $assetService = app(WorkspaceThemeAssetService::class);

        return $schema->components([
            Section::make('Thông tin asset')
                ->schema([
                    Select::make('slot_type')
                        ->label('Loại asset')
                        ->options(collect($assetService->slotDefinitions())
                            ->mapWithKeys(fn (array $definition, string $slotType) => [$slotType => $definition['label']])
                            ->all())
                        ->required()
                        ->live(),
                    TextInput::make('display_name')
                        ->label('Tên hiển thị')
                        ->required()
                        ->maxLength(255),
                    FileUpload::make('asset_path')
                        ->label('Tệp asset')
                        ->disk('public')
                        ->directory(fn (callable $get): string => $assetService->storageDirectory(null, (string) ($get('slot_type') ?: 'background')))
                        ->visibility('public')
                        ->acceptedFileTypes(fn (callable $get): array => $assetService->acceptedMimeTypes((string) ($get('slot_type') ?: 'background')))
                        ->helperText('Mũi tên vòng quay chỉ nhận PNG. Các slot còn lại nhận PNG/JPG/WEBP/SVG.')
                        ->required()
                        ->image()
                        ->previewable(false)
                        ->openable()
                        ->downloadable(),
                    Placeholder::make('asset_preview')
                        ->label('Xem trước asset hiện tại')
                        ->columnSpanFull()
                        ->content(function (callable $get): HtmlString {
                            $assetPath = $get('asset_path');

                            if (! filled($assetPath) || ! is_string($assetPath)) {
                                return new HtmlString('<div style="color:#a1a1aa;">Chưa có ảnh nào được tải lên.</div>');
                            }

                            $url = Storage::disk('public')->url($assetPath);

                            return new HtmlString(sprintf(
                                '<div style="padding:16px; border:1px solid rgba(255,255,255,0.08); border-radius:18px; background:rgba(255,255,255,0.02);">
                                    <img src="%s" alt="%s" style="display:block; max-width:320px; max-height:220px; width:auto; height:auto; border-radius:12px; object-fit:contain; background:rgba(255,255,255,0.03);" />
                                </div>',
                                e($url),
                                e((string) ($get('display_name') ?: 'Asset preview')),
                            ));
                        }),
                    Toggle::make('is_active')
                        ->label('Đang bật')
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label('Thứ tự')
                        ->integer()
                        ->default(0)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $slotDefinitions = app(WorkspaceThemeAssetService::class)->slotDefinitions();

        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Tên asset')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slot_type')
                    ->label('Loại')
                    ->formatStateUsing(fn (string $state): string => $slotDefinitions[$state]['label'] ?? $state)
                    ->badge(),
                TextColumn::make('source_kind')
                    ->label('Nguồn')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'builtin' ? 'Mặc định' : 'Upload'),
                IconColumn::make('is_active')
                    ->label('Hiển thị')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->since(),
            ])
            ->defaultSort('slot_type')
            ->defaultSort('sort_order');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkspaceThemeAssets::route('/'),
            'create' => CreateWorkspaceThemeAsset::route('/create'),
            'edit' => EditWorkspaceThemeAsset::route('/{record}/edit'),
        ];
    }
}
