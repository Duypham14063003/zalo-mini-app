<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">Submissions</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $game->name }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('games.activity', $game) }}" class="admin-secondary-btn">Spin history</a>
                <a href="{{ route('games.edit', $game) }}" class="admin-primary-btn">Mo builder</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="admin-panel p-6">
                <form class="flex flex-col gap-3 md:flex-row">
                    <input class="admin-soft-input mt-0" name="q" value="{{ $keyword }}" placeholder="Tim theo ten hoac so dien thoai">
                    <button class="admin-secondary-btn" type="submit">Loc</button>
                </form>
            </div>

            <div class="admin-panel overflow-hidden">
                <div class="divide-y divide-slate-100">
                    @forelse ($submissions as $submission)
                        <div class="grid gap-4 px-6 py-5 lg:grid-cols-[240px_1fr]">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $submission->player?->full_name ?? 'Unknown player' }}</p>
                                <p class="text-sm text-slate-500">{{ $submission->player?->phone }}</p>
                                <p class="mt-2 text-xs text-slate-400">{{ optional($submission->submitted_at)->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                                @foreach (($submission->payload ?? []) as $key => $value)
                                    <div class="py-1">
                                        <span class="font-semibold text-slate-900">{{ $key }}:</span>
                                        <span>{{ is_array($value) ? json_encode($value) : $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center text-sm text-slate-500">
                            Chua co user nao submit form.
                        </div>
                    @endforelse
                </div>

                @if ($submissions->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $submissions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
