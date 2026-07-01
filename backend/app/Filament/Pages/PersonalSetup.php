<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PersonalSetup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Setup cá nhân';

    protected static ?string $title = 'Setup cá nhân';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.personal-setup';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'name' => $user?->name,
            'email' => $user?->email,
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin tài khoản')
                    ->description('Cập nhật thông tin hiển thị và email đăng nhập của bạn.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Họ và tên')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->rule(fn () => Rule::unique('users', 'email')->ignore(Auth::id())),
                    ])
                    ->columns(2),
                Section::make('Đổi mật khẩu')
                    ->description('Chỉ nhập phần này khi bạn muốn đổi mật khẩu.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Mật khẩu hiện tại')
                            ->password()
                            ->revealable(),
                        TextInput::make('password')
                            ->label('Mật khẩu mới')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->same('password_confirmation'),
                        TextInput::make('password_confirmation')
                            ->label('Xác nhận mật khẩu mới')
                            ->password()
                            ->revealable()
                            ->same('password'),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $user = Auth::user();

        abort_unless($user, 403);

        $data = $this->form->getState();
        $wantsToChangePassword = filled($data['password'] ?? null) || filled($data['password_confirmation'] ?? null);

        if ($wantsToChangePassword && ! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
            throw ValidationException::withMessages([
                'data.current_password' => 'Mật khẩu hiện tại không đúng.',
            ]);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if ($wantsToChangePassword) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        $this->form->fill([
            'name' => $user->fresh()->name,
            'email' => $user->fresh()->email,
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        Notification::make()
            ->title('Đã lưu setup cá nhân')
            ->success()
            ->send();
    }
}
