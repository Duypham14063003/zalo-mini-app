<?php

namespace App\Filament\Resources;

use App\Enums\GameStatus;
use App\Filament\Resources\GameResource\Pages\EditGame;
use App\Filament\Resources\GameResource\Pages\ListGames;
use App\Models\Game;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-gift-top';

    protected static ?string $navigationLabel = 'Trò chơi';

    protected static ?string $modelLabel = 'trò chơi';

    protected static ?string $pluralModelLabel = 'trò chơi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Các bước cấu hình')
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Cấu hình chung')
                        ->schema([
                            Section::make('Thông tin trò chơi')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Tên trò chơi')
                                        ->required(),
                                    TextInput::make('public_slug')
                                        ->label('Slug công khai')
                                        ->required(),
                                    Select::make('status')
                                        ->label('Trạng thái')
                                        ->options([
                                            GameStatus::Draft->value => 'Bản nháp',
                                            GameStatus::Active->value => 'Đang hoạt động',
                                            GameStatus::Inactive->value => 'Tạm dừng',
                                        ])
                                        ->required(),
                                    Textarea::make('game_description')
                                        ->label('Mô tả')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),
                    Tab::make('Phần thưởng')
                        ->schema([
                            Section::make('Thiết lập lượt quay')
                                ->schema([
                                    Toggle::make('requires_reward_code')
                                        ->label('Yêu cầu mã dự thưởng'),
                                    TextInput::make('max_spins_per_player')
                                        ->label('Số lượt quay tối đa / người dùng')
                                        ->integer()
                                        ->minValue(1)
                                        ->default(1)
                                        ->required(),
                                ])
                                ->columns(2),
                            Section::make('Danh sách phần thưởng')
                                ->schema([
                                    Repeater::make('prizes')
                                        ->label('')
                                        ->defaultItems(0)
                                        ->schema([
                                            Hidden::make('id'),
                                            TextInput::make('code')
                                                ->label('Mã'),
                                            TextInput::make('label')
                                                ->label('Tên phần thưởng')
                                                ->required(),
                                            Textarea::make('description')
                                                ->label('Mô tả')
                                                ->rows(2),
                                            TextInput::make('quota')
                                                ->label('Số lượng')
                                                ->integer()
                                                ->minValue(0),
                                            TextInput::make('weight')
                                                ->label('Trọng số')
                                                ->integer()
                                                ->minValue(0)
                                                ->default(0),
                                            Toggle::make('is_active')
                                                ->label('Đang bật')
                                                ->default(true),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Thiết kế vòng quay')
                        ->schema([
                            Section::make('Giao diện vòng quay')
                                ->schema([
                                    ColorPicker::make('primary_color')
                                        ->label('Màu chính')
                                        ->required()
                                        ->default('#f9c667')
                                        ->live(),
                                    ColorPicker::make('secondary_color')
                                        ->label('Màu phụ')
                                        ->required()
                                        ->default('#fff8e4')
                                        ->live(),
                                    ColorPicker::make('accent_color')
                                        ->label('Màu nhấn')
                                        ->required()
                                        ->default('#d79e2f')
                                        ->live(),
                                    Hidden::make('palette_preset')
                                        ->default('mint')
                                        ->required()
                                        ->live(),
                                    ViewField::make('palette_preset_picker')
                                        ->label('Bộ màu nhanh')
                                        ->view('filament.forms.wheel-option-picker')
                                        ->viewData([
                                            'options' => static::palettePresetCatalog(),
                                            'variant' => 'palette',
                                            'targetStatePath' => 'data.palette_preset',
                                        ])
                                        ->dehydrated(false)
                                        ->live(),
                                    Hidden::make('border_preset')
                                        ->default('pink-star')
                                        ->required()
                                        ->live(),
                                    ViewField::make('border_preset_picker')
                                        ->label('Viền vòng quay')
                                        ->view('filament.forms.wheel-option-picker')
                                        ->viewData([
                                            'options' => static::borderPresetCatalog(),
                                            'variant' => 'border',
                                            'targetStatePath' => 'data.border_preset',
                                        ])
                                        ->dehydrated(false)
                                        ->live(),
                                    FileUpload::make('border_asset_path')
                                        ->label('Upload viền ngoài')
                                        ->disk('public')
                                        ->directory('wheel-borders')
                                        ->visibility('public')
                                        ->image()
                                        ->imageEditor()
                                        ->helperText('Nếu có ảnh upload, preview và mobile sẽ ưu tiên ảnh này thay vì preset viền mặc định.')
                                        ->live(),
                                    Hidden::make('pointer_preset')
                                        ->default('teardrop-gold')
                                        ->dehydrated()
                                        ->live(),
                                    TextInput::make('center_label')
                                        ->label('Nhãn trung tâm')
                                        ->required()
                                        ->default('19T')
                                        ->live(),
                                    Hidden::make('background_style')
                                        ->default('warm_gradient')
                                        ->required()
                                        ->live(),
                                    ViewField::make('background_style_picker')
                                        ->label('Nền')
                                        ->view('filament.forms.wheel-option-picker')
                                        ->viewData([
                                            'options' => static::backgroundPresetCatalog(),
                                            'variant' => 'background',
                                            'targetStatePath' => 'data.background_style',
                                        ])
                                        ->dehydrated(false)
                                        ->live(),
                                    FileUpload::make('background_asset_path')
                                        ->label('Upload background')
                                        ->disk('public')
                                        ->directory('wheel-backgrounds')
                                        ->visibility('public')
                                        ->image()
                                        ->imageEditor()
                                        ->helperText('Nếu có ảnh upload, preview và mobile sẽ ưu tiên ảnh này thay vì preset nền mặc định.')
                                        ->live(),
                                    TextInput::make('preview_note')
                                        ->label('Ghi chú xem trước')
                                        ->default('Quay ngay')
                                        ->live(),
                                    Placeholder::make('design_preview')
                                        ->label('Xem trước giao diện')
                                        ->columnSpanFull()
                                        ->content(function ($get): HtmlString {
                                            $primary = (string) ($get('primary_color') ?: '#f9c667');
                                            $secondary = (string) ($get('secondary_color') ?: '#fff8e4');
                                            $accent = (string) ($get('accent_color') ?: '#d79e2f');
                                            $centerLabel = e((string) ($get('center_label') ?: '19T'));
                                            $previewNote = e((string) ($get('preview_note') ?: 'Quay ngay'));
                                            $customBorderUrl = static::storageAssetUrl($get('border_asset_path'));
                                            $customBackgroundUrl = static::storageAssetUrl($get('background_asset_path'));
                                            $palettePreset = match ((string) $get('palette_preset')) {
                                                'marine' => ['#7dc4ff', '#ffb15c', '#8fd0ff', '#ff9a4d', '#85b8f8', '#ffc56f'],
                                                'soft-pop' => ['#f8b3d0', '#ffe08a', '#a6d8ff', '#ffb49d', '#cab8ff', '#9fe3c2'],
                                                'mint' => ['#bfe8d3', '#f8d9ae', '#93d8d1', '#f6bfba', '#c8f2e6', '#f0d8a8'],
                                                'candy' => ['#ff8aa7', '#ffd36e', '#ffb0c1', '#a5d9ff', '#ffa06b', '#d6b8ff'],
                                                'neon' => ['#1cc0b8', '#ffbb66', '#b3e5fc', '#fdfdfd', '#ff7a59', '#9be564'],
                                                default => ['#ffb35d', '#9ad0ff', '#ff9950', '#89c4ff', '#ffc96a', '#8ebdf6'],
                                            };
                                            $wheelGradient = sprintf(
                                                'conic-gradient(from -90deg, %1$s 0deg 60deg, %2$s 60deg 120deg, %3$s 120deg 180deg, %4$s 180deg 240deg, %5$s 240deg 300deg, %6$s 300deg 360deg)',
                                                ...array_map('e', $palettePreset),
                                            );

                                            $background = static::backgroundPreviewStyle((string) ($get('background_style') ?: 'warm_gradient'));
                                            $catalog = static::borderPresetCatalog();
                                            $selectedPreset = $catalog[(string) ($get('border_preset') ?: 'classic-red')] ?? reset($catalog);
                                            $selectedPresetAsset = isset($selectedPreset['asset'])
                                                ? static::wheelAssetDataUri($selectedPreset['asset'])
                                                : null;

                                            return new HtmlString(sprintf(
                                                '<div style="border-radius:24px; border:1px solid #f2e6c5; background:#fffdfa; padding:20px; box-shadow:0 18px 40px rgba(125, 90, 20, 0.08);">
                                                    <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-start;">
                                                        <div style="flex:0 0 300px; max-width:300px; margin-inline:auto;">
                                                            <div style="border-radius:32px; padding:18px; background:%s; box-shadow:0 24px 40px rgba(217, 169, 54, 0.14); overflow:hidden;">
                                                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                                                                    <div style="font-size:12px; font-weight:700; color:#4b5563;">Mini App Preview</div>
                                                                    <div style="padding:8px 14px; border-radius:999px; border:1px solid rgba(17,24,39,0.08); background:rgba(255,255,255,0.72); font-size:12px; color:#374151;">••• | ✕</div>
                                                                </div>
                                                                <div style="text-align:center; margin-bottom:16px;">
                                                                    <div style="font-size:15px; font-weight:700; color:%s;">Uống an lành, góp ngàn</div>
                                                                    <div style="font-size:34px; line-height:1; font-style:italic; font-weight:500; color:%s; margin-top:8px;">Yêu Thương</div>
                                                                </div>
                                                                <div style="position:relative; width:210px; height:210px; margin:0 auto;">
                                                                    <div style="position:absolute; inset:0; border-radius:999px; padding:10px; background:%s; box-shadow:0 16px 26px rgba(217, 169, 54, 0.22);">
                                                                        <div style="position:relative; width:100%%; height:100%%; border-radius:999px; overflow:hidden; background:%s; border:8px solid rgba(255,255,255,0.85);">
                                                                            <div style="position:absolute; inset:0; border-radius:999px; background:%s;"></div>
                                                                            <div style="position:absolute; inset:0; border-radius:999px; background:repeating-conic-gradient(from -90deg, rgba(255,255,255,0.18) 0deg 58deg, rgba(255,255,255,0) 58deg 60deg);"></div>
                                                                            %s
                                                                            <div style="position:absolute; inset:50%% auto auto 50%%; transform:translate(-50%%, -50%%); width:72px; height:72px; border-radius:999px; background:%s; border:8px solid rgba(255,255,255,0.72); display:flex; align-items:center; justify-content:center; box-shadow:0 10px 18px rgba(217, 169, 54, 0.22);">
                                                                                <span style="font-size:20px; font-weight:800; color:#9a5a18;">%s</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div style="margin-top:18px; text-align:center;">
                                                                    <button type="button" style="border:none; border-radius:14px; padding:12px 24px; background:linear-gradient(180deg, %s 0%%, #8f1ea9 100%%); color:#fff; font-size:18px; font-weight:800; box-shadow:0 14px 22px rgba(121, 28, 149, 0.22);">%s</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div style="flex:1 1 340px; min-width:280px; display:grid; gap:14px;">
                                                            <div style="border-radius:20px; border:1px solid #f1e6c8; background:#fff; padding:16px;">
                                                                <div style="font-size:16px; font-weight:800; color:#111827; margin-bottom:8px;">Preview người dùng sẽ thấy</div>
                                                                <div style="font-size:14px; color:#6b7280; line-height:1.6;">Preview này mô phỏng nhanh bố cục màn hình quay để người tạo game dễ cảm nhận màu sắc và cảm giác tổng thể trước khi xuất bản.</div>
                                                            </div>
                                                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                                                <div style="flex:1 1 170px; border-radius:18px; border:1px solid #f2ead7; background:#fff; padding:14px;">
                                                                    <div style="font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.08em;">Màu chính</div>
                                                                    <div style="display:flex; align-items:center; gap:10px; margin-top:10px;">
                                                                        <span style="display:inline-block; width:18px; height:18px; border-radius:999px; background:%s; border:2px solid #fff; box-shadow:0 0 0 1px rgba(0,0,0,0.08);"></span>
                                                                        <strong style="font-size:14px; color:#374151;">%s</strong>
                                                                    </div>
                                                                </div>
                                                                <div style="flex:1 1 170px; border-radius:18px; border:1px solid #f2ead7; background:#fff; padding:14px;">
                                                                    <div style="font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.08em;">Màu phụ</div>
                                                                    <div style="display:flex; align-items:center; gap:10px; margin-top:10px;">
                                                                        <span style="display:inline-block; width:18px; height:18px; border-radius:999px; background:%s; border:2px solid #fff; box-shadow:0 0 0 1px rgba(0,0,0,0.08);"></span>
                                                                        <strong style="font-size:14px; color:#374151;">%s</strong>
                                                                    </div>
                                                                </div>
                                                                <div style="flex:1 1 170px; border-radius:18px; border:1px solid #f2ead7; background:#fff; padding:14px;">
                                                                    <div style="font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.08em;">Màu nhấn</div>
                                                                    <div style="display:flex; align-items:center; gap:10px; margin-top:10px;">
                                                                        <span style="display:inline-block; width:18px; height:18px; border-radius:999px; background:%s; border:2px solid #fff; box-shadow:0 0 0 1px rgba(0,0,0,0.08);"></span>
                                                                        <strong style="font-size:14px; color:#374151;">%s</strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                                                <div style="flex:1 1 170px; border-radius:18px; border:1px solid #f2ead7; background:#fff; padding:14px;">
                                                                    <div style="font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.08em;">Preset</div>
                                                                    <div style="margin-top:8px; font-size:15px; font-weight:700; color:#374151;">%s</div>
                                                                </div>
                                                                <div style="flex:1 1 170px; border-radius:18px; border:1px solid #f2ead7; background:#fff; padding:14px;">
                                                                    <div style="font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.08em;">Nhãn trung tâm</div>
                                                                    <div style="margin-top:8px; font-size:15px; font-weight:700; color:#374151;">%s</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>',
                                                $customBackgroundUrl
                                                    ? 'center / cover no-repeat url('.e($customBackgroundUrl).'), linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.10))'
                                                    : e($background),
                                                e($accent),
                                                e($accent),
                                                $customBorderUrl
                                                    ? 'transparent url('.e($customBorderUrl).') center / contain no-repeat'
                                                    : ($selectedPresetAsset
                                                        ? 'transparent'
                                                        : 'linear-gradient(135deg, #ff5034 0%, #ff9d29 48%, #ffdc72 100%)'),
                                                e($secondary),
                                                e($wheelGradient),
                                                $selectedPresetAsset
                                                    ? sprintf(
                                                        '<div style="position:absolute; inset:0; border-radius:999px; background:center / contain no-repeat url(%s);"></div>',
                                                        e($customBorderUrl ?: $selectedPresetAsset)
                                                    )
                                                    : '',
                                                e($secondary),
                                                $centerLabel,
                                                e($accent),
                                                $previewNote,
                                                e($primary),
                                                e($primary),
                                                e($secondary),
                                                e($secondary),
                                                e($accent),
                                                e($accent),
                                                e((string) $get('palette_preset')),
                                                $centerLabel,
                                            ));
                                        }),
                                ])
                                ->columns(3),
                        ]),
                    Tab::make('Nội dung & chuyển hướng')
                        ->schema([
                            Section::make('Nội dung hiển thị')
                                ->schema([
                                    TextInput::make('title')->label('Tiêu đề')->required(),
                                    TextInput::make('subtitle')->label('Tiêu đề phụ')->required(),
                                    Textarea::make('presentation_description')->label('Mô tả hiển thị')->rows(3)->columnSpanFull(),
                                    TextInput::make('spin_button')->label('Nút quay ngay')->required(),
                                    TextInput::make('continue_button')->label('Nút tiếp tục')->required(),
                                    TextInput::make('loading_message')->label('Thông báo đang tải')->required(),
                                ])
                                ->columns(2),
                            Section::make('Biểu mẫu người chơi')
                                ->schema([
                                    Repeater::make('form_fields')
                                        ->label('')
                                        ->defaultItems(0)
                                        ->schema([
                                            Hidden::make('id'),
                                            TextInput::make('field_key')->label('Khóa trường')->required(),
                                            Select::make('type')
                                                ->label('Loại trường')
                                                ->options([
                                                    'text' => 'Văn bản',
                                                    'tel' => 'Số điện thoại',
                                                    'select' => 'Danh sách chọn',
                                                ])
                                                ->required(),
                                            TextInput::make('label')->label('Nhãn hiển thị')->required(),
                                            TextInput::make('placeholder')->label('Gợi ý nhập'),
                                            TextInput::make('help_text')->label('Ghi chú trợ giúp'),
                                            Textarea::make('options_text')
                                                ->label('Tùy chọn')
                                                ->rows(3)
                                                ->helperText('Mỗi dòng là một lựa chọn cho trường kiểu danh sách.'),
                                            Toggle::make('is_required')->label('Bắt buộc')->default(false),
                                            Toggle::make('is_active')->label('Hiển thị')->default(true),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Chuyển hướng sau khi nhận thưởng')
                                ->schema([
                                    TextInput::make('redirect_action')->label('Hành động')->required(),
                                    TextInput::make('redirect_target_type')->label('Loại đích đến'),
                                    TextInput::make('redirect_target_value')->label('Giá trị đích đến'),
                                    TextInput::make('redirect_fallback_value')->label('Giá trị dự phòng'),
                                    Textarea::make('redirect_message_template')
                                        ->label('Tin nhắn OA')
                                        ->rows(3)
                                        ->helperText('Dùng khi mở chat OA bằng Zalo SDK. Nên nhập OA ID vào "Giá trị đích đến", ví dụ target_type = oa_chat, target_value = 123456789.'),
                                ])
                                ->columns(2),
                            Section::make('Liên kết triển khai')
                                ->schema([
                                    TextInput::make('launch_public_identifier')
                                        ->label('Public ID')
                                        ->disabled()
                                        ->copyable()
                                        ->dehydrated(false),
                                    TextInput::make('launch_status_summary')
                                        ->label('Trạng thái launch')
                                        ->disabled()
                                        ->dehydrated(false),
                                    TextInput::make('launch_runtime_url')
                                        ->label('Link xem trước')
                                        ->disabled()
                                        ->copyable()
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                    TextInput::make('launch_miniapp_path')
                                        ->label('Mini App path')
                                        ->disabled()
                                        ->copyable()
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                    TextInput::make('launch_zalo_url')
                                        ->label('Link mở trong Zalo')
                                        ->disabled()
                                        ->copyable()
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                    TextInput::make('launch_qr_payload')
                                        ->label('QR payload')
                                        ->disabled()
                                        ->copyable()
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                    Placeholder::make('launch_qr_preview')
                                        ->label('Mã QR')
                                        ->content(function ($get): HtmlString {
                                            $qrPreviewUrl = (string) $get('launch_qr_preview_url');

                                            if ($qrPreviewUrl === '') {
                                                return new HtmlString('<div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500">Chưa có dữ liệu QR để hiển thị.</div>');
                                            }

                                            return new HtmlString(sprintf(
                                                '<div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"><img src="%s" alt="QR launch link" class="mx-auto h-56 w-56 rounded-xl object-contain" /><p class="mt-3 text-xs text-gray-500">Quét mã này để mở liên kết launch hiện tại.</p></div>',
                                                e($qrPreviewUrl),
                                            ));
                                        })
                                        ->columnSpanFull(),
                                    Hidden::make('launch_qr_preview_url')
                                        ->dehydrated(false),
                                    Textarea::make('launch_status_message')
                                        ->label('Ghi chú triển khai')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Trò chơi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('workspace.name')
                    ->label('Workspace')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->state(fn (Game $record) => $record->status?->value ?? 'draft')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => 'Bản nháp',
                        'active' => 'Đang hoạt động',
                        'inactive' => 'Tạm dừng',
                        default => $state,
                    }),
                TextColumn::make('builderConfig.publication_status')
                    ->label('Xuất bản')
                    ->state(fn (Game $record) => (bool) $record->published_at
                        && $record->builderConfig?->publication_status === 'published'
                        && ($record->status?->value ?? $record->status) !== GameStatus::Draft->value
                        ? 'published'
                        : 'draft')
                    ->badge()
                    ->default('draft')
                    ->formatStateUsing(fn ($state) => match ((string) $state) {
                        'published' => 'Đã xuất bản',
                        'draft' => 'Bản nháp',
                        default => $state,
                    }),
                TextColumn::make('publicIds.slug')
                    ->label('Slug')
                    ->listWithLineBreaks()
                    ->limitList(1),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->since(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['workspace', 'builderConfig', 'publicIds']);

        $user = Filament::auth()->user();

        if ($user && ! $user->isPlatformAdmin()) {
            $query->whereIn('workspace_id', $user->managedWorkspaceIds());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGames::route('/'),
            'edit' => EditGame::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function palettePresetCatalog(): array
    {
        return [
            'mint' => ['label' => 'Mint pastel', 'colors' => ['#f5d0b5', '#f5f1ee', '#abd8d1', '#76a8a9']],
            'sunrise' => ['label' => 'Bình minh', 'colors' => ['#fdf1d0', '#ffcf64', '#ff914d', '#ff5040']],
            'marine' => ['label' => 'Biển xanh', 'colors' => ['#edf5ff', '#1e63a4', '#114d86', '#0c355e']],
            'soft-pop' => ['label' => 'Pastel nổi bật', 'colors' => ['#ffd8bf', '#f7d9cd', '#a8a0f1', '#7b58e5']],
            'candy' => ['label' => 'Kẹo ngọt', 'colors' => ['#dbdbdb', '#f6d1c5', '#f2a7b8', '#f98d9b']],
            'neon' => ['label' => 'Neon', 'colors' => ['#20b9ad', '#b2dee7', '#f7f8fc', '#ffb869']],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function borderPresetCatalog(): array
    {
        return [
            'pink-star' => ['label' => 'Hồng ánh sao', 'asset' => 'bg-vongquay/v4.png'],
            'classic-red' => ['label' => 'Đỏ cổ điển', 'asset' => 'bg-vongquay/v2.png'],
            'gold-ring' => ['label' => 'Vòng vàng', 'asset' => 'bg-vongquay/v3.png'],
            'violet-glow' => ['label' => 'Tím phát sáng', 'asset' => 'bg-vongquay/v1.png'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function backgroundPresetCatalog(): array
    {
        return [
            'warm_gradient' => [
                'label' => 'Chuyển sắc ấm',
                'background' => 'linear-gradient(180deg, #fff6ea 0%, #fff8eb 55%, #fff3dd 100%)',
                'overlay' => 'radial-gradient(circle at top left, rgba(249, 198, 103, 0.42), transparent 32%), radial-gradient(circle at bottom center, rgba(245, 226, 140, 0.56), transparent 26%)',
            ],
            'soft_purple' => [
                'label' => 'Tím dịu',
                'background' => 'linear-gradient(180deg, #f9efff 0%, #fff7fd 52%, #fffdf7 100%)',
                'overlay' => 'radial-gradient(circle at top left, rgba(219, 188, 255, 0.48), transparent 30%), radial-gradient(circle at bottom center, rgba(255, 219, 240, 0.54), transparent 28%)',
            ],
            'pastel_grass' => [
                'label' => 'Cỏ pastel',
                'background' => 'linear-gradient(180deg, #fff8e8 0%, #fffef4 58%, #eff8d4 100%)',
                'overlay' => 'radial-gradient(circle at top left, rgba(255, 216, 171, 0.52), transparent 30%), radial-gradient(circle at bottom center, rgba(203, 237, 158, 0.48), transparent 30%)',
            ],
            'bg_showcase' => [
                'label' => 'BG mặc định',
                'asset' => 'bg/bg1.png',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function pointerPresetCatalog(): array
    {
        return [
            'teardrop-gold' => [
                'label' => 'Giọt vàng',
                'shape' => 'polygon(50% 0%, 88% 34%, 68% 100%, 32% 100%, 12% 34%)',
                'background' => 'linear-gradient(180deg, #ffd052 0%, #d4961d 100%)',
            ],
            'triangle-fire' => [
                'label' => 'Tam giác lửa',
                'shape' => 'polygon(50% 0%, 100% 100%, 0% 100%)',
                'background' => 'linear-gradient(180deg, #fff3be 0%, #efab37 38%, #ca6d14 100%)',
            ],
            'diamond-soft' => [
                'label' => 'Kim cương mềm',
                'shape' => 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)',
                'background' => 'linear-gradient(180deg, #fff1ff 0%, #d9b4ff 48%, #8d62ff 100%)',
            ],
        ];
    }

    public static function backgroundPreviewStyle(string $style): string
    {
        $preset = static::backgroundPresetCatalog()[$style] ?? static::backgroundPresetCatalog()['warm_gradient'];

        if (isset($preset['asset'])) {
            $assetDataUri = static::wheelAssetDataUri($preset['asset']);

            if ($assetDataUri) {
                return 'center / cover no-repeat url('.$assetDataUri.'), linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.08))';
            }
        }

        return trim((string) (($preset['overlay'] ?? '').', '.($preset['background'] ?? 'linear-gradient(180deg, #fff6ea 0%, #fff8eb 55%, #fff3dd 100%)')), ', ');
    }

    public static function wheelAssetDataUri(string $relativePath): ?string
    {
        $path = base_path($relativePath);

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $mimeType = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    public static function storageAssetUrl(mixed $path): ?string
    {
        $value = trim((string) ($path ?? ''));

        if ($value === '') {
            return null;
        }

        return Storage::disk('public')->url($value);
    }
}
