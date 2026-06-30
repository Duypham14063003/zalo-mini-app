@php
    $navItems = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard')],
        ['label' => 'Games', 'route' => 'games.index', 'active' => request()->routeIs('games.*')],
        ['label' => 'Profile', 'route' => 'profile.edit', 'active' => request()->routeIs('profile.*')],
    ];
@endphp

<aside class="admin-sidebar">
    <div class="border-b border-slate-700 px-5 py-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/10 font-black text-white">19T</div>
                <div>
                    <div class="text-sm font-semibold">Game Builder</div>
                    <div class="text-xs text-slate-400">Lucky Wheel Admin</div>
                </div>
            </div>
            <span class="text-slate-500">▼</span>
        </div>
    </div>

    <div class="flex-1 space-y-2 px-4 py-5">
        @foreach ($navItems as $item)
            <a
                href="{{ route($item['route']) }}"
                class="admin-sidebar-link {{ $item['active'] ? 'admin-sidebar-link-active' : '' }}"
            >
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/5 text-xs font-bold">
                    {{ strtoupper(substr($item['label'], 0, 1)) }}
                </span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="border-t border-slate-700 p-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="admin-sidebar-link w-full justify-start" type="submit">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/5 text-xs font-bold">↩</span>
                <span>Log Out</span>
            </button>
        </form>
    </div>
</aside>
