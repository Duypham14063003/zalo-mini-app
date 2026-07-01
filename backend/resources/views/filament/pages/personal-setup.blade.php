<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-3xl border border-amber-100 bg-white p-6 shadow-sm">
            <div class="max-w-3xl">
                <h2 class="text-xl font-bold tracking-tight text-gray-950">Thiết lập cá nhân</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Quản lý thông tin tài khoản của bạn tại đây. Nếu muốn đổi mật khẩu, hãy nhập mật khẩu hiện tại để xác nhận.
                </p>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center justify-end">
                <x-filament::button type="submit" size="lg">
                    Lưu thay đổi
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
