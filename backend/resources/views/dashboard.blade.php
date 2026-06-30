<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    {{ __('Tong quan van hanh game builder') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ __('Quan ly workspace, game quay thuong va trang thai publish tu mot man hinh.') }}
                </p>
            </div>
            <div class="rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-800">
                {{ $stats['role_label'] }}
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="admin-stat-card bg-slate-900 text-white">
                    <p class="text-sm text-slate-300">Workspace</p>
                    <p class="mt-3 text-4xl font-semibold">{{ $stats['workspace_count'] }}</p>
                    <p class="mt-2 text-sm text-slate-400">Khach hang dang quan ly</p>
                </div>
                <div class="admin-stat-card bg-white">
                    <p class="text-sm text-slate-500">Games</p>
                    <p class="mt-3 text-4xl font-semibold text-slate-900">{{ $stats['game_count'] }}</p>
                    <p class="mt-2 text-sm text-slate-400">Builder dang hoat dong</p>
                </div>
                <div class="admin-stat-card bg-white">
                    <p class="text-sm text-slate-500">Submissions</p>
                    <p class="mt-3 text-4xl font-semibold text-slate-900">{{ $stats['submission_count'] }}</p>
                    <p class="mt-2 text-sm text-slate-400">Du lieu user da thu thap</p>
                </div>
                <div class="admin-stat-card bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
                    <p class="text-sm text-sky-100">Builder mode</p>
                    <p class="mt-3 text-2xl font-semibold">Lucky Wheel</p>
                    <p class="mt-2 text-sm text-sky-100">Preview + publish workflow</p>
                </div>
            </div>

            <div class="grid gap-8 xl:grid-cols-[1.25fr_0.75fr]">
                <section class="admin-panel overflow-hidden">
                    <div class="admin-panel-header">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Games dang quan ly</h3>
                            <p class="mt-1 text-sm text-slate-500">Mo builder de sua nhanh giao dien, phan thuong va publish state.</p>
                        </div>
                        <a href="{{ route('games.index') }}" class="admin-secondary-btn">Xem tat ca</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach ($games as $game)
                            <div class="flex flex-col gap-4 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-lg font-semibold text-slate-900">{{ $game->name }}</h4>
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-500">
                                            {{ $game->builderConfig?->publication_status ?? 'draft' }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold text-white" style="background-color: {{ $game->theme?->primary_color ?? '#f9c667' }}">
                                            {{ $game->publicIds->first()?->slug ?? $game->slug }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-500">{{ $game->workspace->name }}</p>
                                    <div class="flex flex-wrap gap-4 text-sm text-slate-500">
                                        <span>{{ $game->players_count }} players</span>
                                        <span>{{ $game->prizes_count }} prizes</span>
                                        <span>{{ $game->rules?->max_spins_per_player ?? 1 }} spin / player</span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('games.reward-codes', $game) }}" class="admin-secondary-btn">Reward codes</a>
                                    <a href="{{ route('games.edit', $game) }}" class="admin-primary-btn">Mo builder</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="admin-panel overflow-hidden">
                    <div class="admin-panel-header">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Submissions moi nhat</h3>
                            <p class="mt-1 text-sm text-slate-500">Kiem tra nhanh du lieu user de lai tu mini app.</p>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($recentSubmissions as $submission)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $submission->player?->full_name ?? 'Unknown player' }}</p>
                                        <p class="text-sm text-slate-500">{{ $submission->game?->name }}</p>
                                    </div>
                                    <p class="text-xs text-slate-400">{{ optional($submission->submitted_at)->diffForHumans() }}</p>
                                </div>
                                <div class="mt-3 rounded-2xl bg-slate-50 p-3 text-xs text-slate-600">
                                    @foreach (($submission->payload ?? []) as $key => $value)
                                        <div><span class="font-semibold">{{ $key }}:</span> {{ is_array($value) ? json_encode($value) : $value }}</div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-12 text-center text-sm text-slate-500">
                                Chua co du lieu submit.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
