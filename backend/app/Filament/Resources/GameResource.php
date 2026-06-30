<?php

namespace App\Filament\Resources;

use App\Enums\GameStatus;
use App\Filament\Resources\GameResource\Pages\EditGame;
use App\Filament\Resources\GameResource\Pages\ListGames;
use App\Models\Game;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

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
                                    Select::make('palette_preset')
                                        ->label('Bộ màu nhanh')
                                        ->options([
                                            'sunrise' => 'Bình minh',
                                            'marine' => 'Biển xanh',
                                            'soft-pop' => 'Pastel nổi bật',
                                            'mint' => 'Bạc hà',
                                            'candy' => 'Kẹo ngọt',
                                            'neon' => 'Neon',
                                        ])
                                        ->live()
                                        ->required(),
                                    Select::make('border_preset')
                                        ->label('Viền vòng quay')
                                        ->options([
                                            'classic-red' => 'Đỏ cổ điển',
                                            'gold-ring' => 'Vòng vàng',
                                            'pink-star' => 'Hồng ánh sao',
                                            'violet-glow' => 'Tím phát sáng',
                                        ])
                                        ->live()
                                        ->required(),
                                    Select::make('pointer_preset')
                                        ->label('Mũi tên')
                                        ->options([
                                            'teardrop-gold' => 'Giọt vàng',
                                            'triangle-fire' => 'Tam giác lửa',
                                            'diamond-soft' => 'Kim cương mềm',
                                        ])
                                        ->live()
                                        ->required(),
                                    TextInput::make('center_label')
                                        ->label('Nhãn trung tâm')
                                        ->required()
                                        ->live(),
                                    Select::make('background_style')
                                        ->label('Nền')
                                        ->options([
                                            'warm_gradient' => 'Chuyển sắc ấm',
                                            'soft_purple' => 'Tím nhạt',
                                            'pastel_grass' => 'Cỏ pastel',
                                        ])
                                        ->live()
                                        ->required(),
                                    TextInput::make('preview_note')
                                        ->label('Ghi chú xem trước')
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

                                            $background = match ((string) $get('background_style')) {
                                                'soft_purple' => 'linear-gradient(180deg, #f7e8ff 0%, #fff5fb 55%, #fffdf8 100%)',
                                                'pastel_grass' => 'linear-gradient(180deg, #fff7e6 0%, #fffdf2 58%, #e5f7bf 100%)',
                                                default => 'radial-gradient(circle at 20% 10%, rgba(249, 198, 103, 0.55), transparent 28%), linear-gradient(180deg, #fff4dc 0%, #fffbf2 62%, #fffef8 100%)',
                                            };
                                            $pointerShadow = match ((string) $get('pointer_preset')) {
                                                'triangle-fire' => '0 10px 20px rgba(255, 118, 64, 0.35)',
                                                'diamond-soft' => '0 10px 20px rgba(127, 90, 240, 0.25)',
                                                default => '0 10px 20px rgba(215, 158, 47, 0.28)',
                                            };
                                            $borderStyle = match ((string) $get('border_preset')) {
                                                'gold-ring' => 'linear-gradient(135deg, #ffef9d 0%, #c58d21 44%, #ffe79f 100%)',
                                                'pink-star' => 'linear-gradient(135deg, #ff69b4 0%, #ff9c7a 50%, #ffd65e 100%)',
                                                'violet-glow' => 'linear-gradient(135deg, #fd85ff 0%, #9657ff 52%, #ffd15c 100%)',
                                                default => 'linear-gradient(135deg, #ff5034 0%, #ff9d29 48%, #ffdc72 100%)',
                                            };

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
                                                                    <div style="position:absolute; top:-4px; left:50%%; transform:translateX(-50%%); width:0; height:0; border-left:16px solid transparent; border-right:16px solid transparent; border-bottom:30px solid %s; filter:drop-shadow(%s); z-index:3;"></div>
                                                                    <div style="position:absolute; inset:0; border-radius:999px; padding:10px; background:%s; box-shadow:0 16px 26px rgba(217, 169, 54, 0.22);">
                                                                        <div style="position:relative; width:100%%; height:100%%; border-radius:999px; overflow:hidden; background:%s; border:8px solid rgba(255,255,255,0.85);">
                                                                            <div style="position:absolute; inset:0; border-radius:999px; background:%s;"></div>
                                                                            <div style="position:absolute; inset:0; border-radius:999px; background:repeating-conic-gradient(from -90deg, rgba(255,255,255,0.18) 0deg 58deg, rgba(255,255,255,0) 58deg 60deg);"></div>
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
                                                e($background),
                                                e($accent),
                                                e($accent),
                                                e($accent),
                                                e($pointerShadow),
                                                e($borderStyle),
                                                e($secondary),
                                                e($wheelGradient),
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
}
