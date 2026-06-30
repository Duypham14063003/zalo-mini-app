<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">Spin history</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $game->name }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('games.claims', $game) }}" class="admin-secondary-btn">Claims</a>
                <a href="{{ route('games.edit', $game) }}" class="admin-primary-btn">Mo builder</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="admin-panel p-6">
                <form>
                    <select name="claim_status" onchange="this.form.submit()" class="rounded-2xl border-slate-200 bg-slate-50 text-sm">
                        <option value="">Tat ca claim status</option>
                        @foreach (['pending', 'claimed', 'fulfilled'] as $status)
                            <option value="{{ $status }}" @selected($claimStatus === $status)>{{ $status }}</option>
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
                                <th class="px-6 py-4">Result</th>
                                <th class="px-6 py-4">Claim</th>
                                <th class="px-6 py-4">Resolved at</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @foreach ($spinResults as $result)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ $result->player?->full_name ?? 'Unknown player' }}</div>
                                        <div class="text-xs text-slate-500">{{ $result->player?->phone }}</div>
                                    </td>
                                    <td class="px-6 py-4">{{ $result->prize?->label ?? 'No prize' }}</td>
                                    <td class="px-6 py-4">{{ $result->result_type }}</td>
                                    <td class="px-6 py-4">{{ $result->claim_status?->value ?? $result->claim_status }}</td>
                                    <td class="px-6 py-4">{{ optional($result->resolved_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $spinResults->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
