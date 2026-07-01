<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">Claims</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $game->name }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('games.winners', $game) }}" class="admin-secondary-btn">Nguoi trung qua</a>
                <a href="{{ route('games.activity', $game) }}" class="admin-secondary-btn">Spin history</a>
                <a href="{{ route('games.edit', $game) }}" class="admin-primary-btn">Mo builder</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="admin-panel p-6">
                <form>
                    <select name="status" onchange="this.form.submit()" class="rounded-2xl border-slate-200 bg-slate-50 text-sm">
                        <option value="">Tat ca</option>
                        @foreach (['claimed', 'fulfilled'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="admin-panel overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50 text-left text-sm font-semibold text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Player</th>
                                <th class="px-6 py-4">Prize</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Claimed at</th>
                                <th class="px-6 py-4">Tac vu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @foreach ($claims as $claim)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ $claim->player?->full_name ?? 'Unknown player' }}</div>
                                        <div class="text-xs text-slate-500">{{ $claim->player?->phone }}</div>
                                    </td>
                                    <td class="px-6 py-4">{{ $claim->spinResult?->prize?->label ?? 'No prize' }}</td>
                                    <td class="px-6 py-4">{{ $claim->status?->value ?? $claim->status }}</td>
                                    <td class="px-6 py-4">{{ optional($claim->claimed_at)->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4">
                                        @if (! $claim->fulfilled_at)
                                            <form method="POST" action="{{ route('games.claims.fulfill', [$game, $claim]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="admin-secondary-btn" type="submit">Fulfill</button>
                                            </form>
                                        @else
                                            <span class="text-xs font-semibold text-emerald-600">Fulfilled</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $claims->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
