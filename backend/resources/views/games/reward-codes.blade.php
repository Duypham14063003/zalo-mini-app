<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">Reward codes</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $game->name }}</p>
            </div>
            <a href="{{ route('games.edit', $game) }}" class="admin-primary-btn">Mo builder</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 xl:grid-cols-[0.55fr_0.45fr]">
                <section class="admin-panel p-6">
                    <h3 class="text-lg font-semibold text-slate-900">Them reward codes</h3>
                    <p class="mt-1 text-sm text-slate-500">Moi dong la mot code. He thong bo qua code trung.</p>
                    <form method="POST" action="{{ route('games.reward-codes.store', $game) }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label class="admin-label">Bulk input</label>
                            <textarea name="codes" rows="8" class="admin-soft-input">{{ old('codes') }}</textarea>
                        </div>
                        <div>
                            <label class="admin-label">Max uses</label>
                            <input type="number" min="1" max="20" name="max_uses" value="{{ old('max_uses', 1) }}" class="admin-soft-input">
                        </div>
                        <button type="submit" class="admin-primary-btn w-full">Luu reward codes</button>
                    </form>
                </section>

                <section class="admin-panel overflow-hidden">
                    <div class="admin-panel-header">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Danh sach codes</h3>
                            <p class="mt-1 text-sm text-slate-500">Theo doi tinh trang su dung.</p>
                        </div>
                        <form>
                            <select name="status" onchange="this.form.submit()" class="rounded-2xl border-slate-200 bg-slate-50 text-sm">
                                <option value="">Tat ca</option>
                                @foreach (['active', 'exhausted', 'disabled'] as $status)
                                    <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50 text-left text-sm font-semibold text-slate-500">
                                <tr>
                                    <th class="px-6 py-4">Code</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Uses</th>
                                    <th class="px-6 py-4">Last player</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                                @foreach ($rewardCodes as $code)
                                    <tr>
                                        <td class="px-6 py-4 font-semibold text-slate-900">{{ $code->code }}</td>
                                        <td class="px-6 py-4">{{ $code->status?->value ?? $code->status }}</td>
                                        <td class="px-6 py-4">{{ $code->used_count }}/{{ $code->max_uses }}</td>
                                        <td class="px-6 py-4">{{ $code->lastUsedByPlayer?->full_name ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $rewardCodes->links() }}
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
