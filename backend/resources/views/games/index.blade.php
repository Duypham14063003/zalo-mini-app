<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">Game Builder</h2>
                <p class="mt-1 text-sm text-slate-500">Danh sach game va trang thai builder, publish, reward codes.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="admin-panel overflow-hidden">
                <div class="admin-panel-header">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Tat ca game</h3>
                        <p class="mt-1 text-sm text-slate-500">Mo builder de chinh sua game theo tung buoc.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50 text-left text-sm font-semibold text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Game</th>
                                <th class="px-6 py-4">Workspace</th>
                                <th class="px-6 py-4">Publish</th>
                                <th class="px-6 py-4">Slug</th>
                                <th class="px-6 py-4">Stats</th>
                                <th class="px-6 py-4">Tac vu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @foreach ($games as $game)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ $game->name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $game->description }}</div>
                                    </td>
                                    <td class="px-6 py-4">{{ $game->workspace->name }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-500">
                                            {{ $game->builderConfig?->publication_status ?? 'draft' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">{{ $game->publicIds->first()?->slug ?? $game->slug }}</td>
                                    <td class="px-6 py-4">
                                        <div>{{ $game->players_count }} players</div>
                                        <div class="text-xs text-slate-500">{{ $game->reward_codes_count }} codes</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('games.edit', $game) }}" class="admin-primary-btn">Builder</a>
                                            <a href="{{ route('games.reward-codes', $game) }}" class="admin-secondary-btn">Codes</a>
                                            <a href="{{ route('games.activity', $game) }}" class="admin-secondary-btn">Activity</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
