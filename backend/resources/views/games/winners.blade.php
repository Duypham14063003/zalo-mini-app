<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">Người trúng quà</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $game->name }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('games.activity', $game) }}" class="admin-secondary-btn">Spin history</a>
                <a href="{{ route('games.claims', $game) }}" class="admin-secondary-btn">Claims</a>
                <a href="{{ route('games.edit', $game) }}" class="admin-primary-btn">Mo builder</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="admin-panel p-6">
                <form class="grid gap-3 md:grid-cols-[1fr_220px_auto]">
                    <input class="admin-soft-input mt-0" name="q" value="{{ $keyword }}" placeholder="Tim theo ten, SDT, email">
                    <select name="claim_status" onchange="this.form.submit()" class="rounded-2xl border-slate-200 bg-slate-50 text-sm">
                        <option value="">Tat ca claim status</option>
                        @foreach (['pending', 'claimed', 'fulfilled'] as $status)
                            <option value="{{ $status }}" @selected($claimStatus === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <button class="admin-secondary-btn" type="submit">Loc</button>
                </form>
            </div>

            <div class="admin-panel overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50 text-left text-sm font-semibold text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Người chơi</th>
                                <th class="px-6 py-4">Phần thưởng</th>
                                <th class="px-6 py-4">Claim status</th>
                                <th class="px-6 py-4">Đã claim</th>
                                <th class="px-6 py-4">Thời gian trúng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($winners as $winner)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ $winner->player?->full_name ?? 'Unknown player' }}</div>
                                        <div class="text-xs text-slate-500">{{ $winner->player?->phone ?: 'Khong co SDT' }}</div>
                                        @if ($winner->player?->email)
                                            <div class="text-xs text-slate-500">{{ $winner->player->email }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ $winner->prize?->label ?? 'No prize' }}</div>
                                        @if ($winner->prize?->description)
                                            <div class="text-xs text-slate-500">{{ $winner->prize->description }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">{{ $winner->claim_status?->value ?? $winner->claim_status }}</td>
                                    <td class="px-6 py-4">
                                        @if ($winner->claim?->claimed_at)
                                            {{ optional($winner->claim->claimed_at)->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-xs text-slate-400">Chua claim</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">{{ optional($winner->resolved_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                        Chua co user nao trung thuong.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $winners->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
