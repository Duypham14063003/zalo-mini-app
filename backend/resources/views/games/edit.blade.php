@php
    $general = old('general', $draftConfig['general'] ?? []);
    $rewards = old('rewards', $draftConfig['rewards'] ?? []);
    $design = old('design', $draftConfig['design'] ?? []);
    $presentation = old('presentation', $draftConfig['presentation'] ?? []);
    $isPublished = (bool) $game->published_at
        && ($builderConfig->publication_status ?? 'draft') === 'published'
        && (($game->status?->value ?? $game->status) !== 'draft');
    $previewState = [
        'general' => $general,
        'rewards' => $rewards,
        'design' => $design,
        'presentation' => $presentation,
        'preview' => $previewConfig['preview'] ?? [],
    ];
    $rewardRows = collect($rewards['prizes'] ?? [])->push([
        'id' => null,
        'code' => '',
        'label' => '',
        'description' => '',
        'quota' => '',
        'weight' => '',
        'is_active' => true,
    ]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-500">Lucky wheel builder</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">
                        Chinh sua game: {{ $game->name }}
                    </h2>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('games.reward-codes', $game) }}" class="admin-secondary-btn">Reward codes</a>
                    <a href="{{ route('games.winners', $game) }}" class="admin-secondary-btn">Nguoi trung qua</a>
                    <a href="{{ route('games.activity', $game) }}" class="admin-secondary-btn">Spin history</a>
                    <a href="{{ route('games.claims', $game) }}" class="admin-secondary-btn">Claims</a>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 xl:gap-10">
                @foreach ($steps as $stepKey => $stepLabel)
                    <div class="admin-step {{ $step === $stepKey ? 'admin-step-active' : '' }}">
                        <span class="admin-step-dot">{{ $loop->iteration }}</span>
                        <span>{{ $stepLabel }}</span>
                        @if (! $loop->last)
                            <span class="hidden h-px w-16 bg-slate-300 xl:block"></span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:px-8 xl:grid-cols-[1.05fr_0.95fr]"
            x-data='@json($previewState)'
        >
            <form method="POST" action="{{ route('games.update', $game) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                <input type="hidden" name="step" value="{{ $step }}">

                <section class="grid gap-4 md:grid-cols-4">
                    <div class="admin-stat-card bg-white">
                        <p class="text-sm text-slate-500">Nguoi choi</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $game->players_count ?? 0 }}</p>
                        <p class="mt-2 text-sm text-slate-400">Tong player cua game</p>
                    </div>
                    <div class="admin-stat-card bg-white">
                        <p class="text-sm text-slate-500">Phan thuong</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $game->prizes_count ?? 0 }}</p>
                        <p class="mt-2 text-sm text-slate-400">Prize dang cau hinh</p>
                    </div>
                    <div class="admin-stat-card bg-white">
                        <p class="text-sm text-slate-500">Nguoi trung qua</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $game->winning_players_count ?? 0 }}</p>
                        <p class="mt-2 text-sm text-slate-400">Player duy nhat da trung</p>
                    </div>
                    <div class="admin-stat-card bg-white">
                        <p class="text-sm text-slate-500">Luot trung</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $game->winning_results_count ?? 0 }}</p>
                        <p class="mt-2 text-sm text-slate-400">Tong ket qua quay trung thuong</p>
                    </div>
                </section>

                @if ($step === 'general')
                    <section class="admin-panel p-6">
                        <h3 class="text-xl font-semibold text-slate-900">1. Cau hinh chung</h3>
                        <p class="mt-2 text-sm text-slate-500">Dat ten game, slug public va trang thai van hanh.</p>
                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="admin-label">Ten game</label>
                                <input name="general[name]" value="{{ $general['name'] ?? '' }}" x-model="general.name" class="admin-soft-input">
                            </div>
                            <div>
                                <label class="admin-label">Slug public</label>
                                <input name="general[slug]" value="{{ $general['slug'] ?? '' }}" x-model="general.slug" class="admin-soft-input">
                            </div>
                            <div>
                                <label class="admin-label">Trang thai</label>
                                <select name="general[status]" x-model="general.status" class="admin-soft-input">
                                    @foreach ($statusOptions as $statusOption)
                                        <option value="{{ $statusOption }}" @selected(($general['status'] ?? '') === $statusOption)>{{ $statusOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="admin-label">Workspace</label>
                                <div class="mt-2 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ $game->workspace->name }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="admin-label">Mo ta</label>
                                <textarea name="general[description]" rows="4" x-model="general.description" class="admin-soft-input">{{ $general['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($step === 'rewards')
                    <section class="admin-panel p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">2. Cau hinh phan thuong</h3>
                                <p class="mt-2 text-sm text-slate-500">Chinh sua qua tang, quota, weight va dieu kien ma du thuong.</p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700">
                                <input type="checkbox" name="rewards[requires_reward_code]" value="1" @checked($rewards['requires_reward_code'] ?? false) class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                Bat buoc ma du thuong
                            </label>
                            <div>
                                <label class="admin-label">So luot quay / player</label>
                                <input type="number" min="1" max="10" name="rewards[max_spins_per_player]" value="{{ $rewards['max_spins_per_player'] ?? 1 }}" class="admin-soft-input">
                            </div>
                        </div>

                        <div class="mt-6 space-y-4">
                            @foreach ($rewardRows as $index => $reward)
                                <div class="rounded-3xl border border-slate-200 p-4">
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <div>
                                            <div class="font-semibold text-slate-900">{{ $reward['code'] ?: 'Prize moi' }}</div>
                                            <div class="text-xs text-slate-500">Dong thuong #{{ $loop->iteration }}</div>
                                        </div>
                                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                            <input type="checkbox" name="rewards[prizes][{{ $index }}][is_active]" value="1" @checked($reward['is_active'] ?? false) class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                            Active
                                        </label>
                                    </div>
                                    <input type="hidden" name="rewards[prizes][{{ $index }}][id]" value="{{ $reward['id'] }}">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="admin-label">Code</label>
                                            <input name="rewards[prizes][{{ $index }}][code]" value="{{ $reward['code'] }}" class="admin-soft-input">
                                        </div>
                                        <div>
                                            <label class="admin-label">Label</label>
                                            <input name="rewards[prizes][{{ $index }}][label]" value="{{ $reward['label'] }}" class="admin-soft-input">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="admin-label">Mo ta</label>
                                            <input name="rewards[prizes][{{ $index }}][description]" value="{{ $reward['description'] }}" class="admin-soft-input">
                                        </div>
                                        <div>
                                            <label class="admin-label">Quota</label>
                                            <input type="number" min="0" name="rewards[prizes][{{ $index }}][quota]" value="{{ $reward['quota'] }}" class="admin-soft-input">
                                        </div>
                                        <div>
                                            <label class="admin-label">Weight</label>
                                            <input type="number" min="0" name="rewards[prizes][{{ $index }}][weight]" value="{{ $reward['weight'] }}" class="admin-soft-input">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($step === 'design')
                    <section class="admin-panel p-6">
                        <h3 class="text-xl font-semibold text-slate-900">3. Thiet ke vong quay</h3>
                        <p class="mt-2 text-sm text-slate-500">Chon palette nhanh, viền vong quay, mui ten va mau chinh.</p>

                        <div class="mt-6">
                            <label class="admin-label">Chon mau nhanh</label>
                            <div class="mt-3 grid gap-4 md:grid-cols-3">
                                @foreach ($palettePresets as $preset)
                                    <label class="cursor-pointer rounded-2xl border border-slate-200 p-3">
                                        <input type="radio" class="hidden" name="design[palette_preset]" value="{{ $preset['id'] }}" @checked(($design['palette_preset'] ?? 'sunrise') === $preset['id'])>
                                        <div class="mb-3 flex overflow-hidden rounded-xl border border-slate-100">
                                            @foreach ($preset['colors'] as $color)
                                                <span class="h-10 flex-1" style="background-color: {{ $color }}"></span>
                                            @endforeach
                                        </div>
                                        <div class="text-sm font-semibold text-slate-700">{{ $preset['label'] }}</div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-3">
                            <div>
                                <label class="admin-label">Primary</label>
                                <input type="color" name="design[primary_color]" value="{{ $design['primary_color'] ?? '#f9c667' }}" x-model="design.primary_color" class="admin-soft-input h-12">
                            </div>
                            <div>
                                <label class="admin-label">Secondary</label>
                                <input type="color" name="design[secondary_color]" value="{{ $design['secondary_color'] ?? '#fff8e4' }}" x-model="design.secondary_color" class="admin-soft-input h-12">
                            </div>
                            <div>
                                <label class="admin-label">Accent</label>
                                <input type="color" name="design[accent_color]" value="{{ $design['accent_color'] ?? '#d79e2f' }}" x-model="design.accent_color" class="admin-soft-input h-12">
                            </div>
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="admin-label">Vien vong quay</label>
                                <select name="design[border_preset]" class="admin-soft-input">
                                    @foreach ($borderPresets as $preset)
                                        <option value="{{ $preset['id'] }}" @selected(($design['border_preset'] ?? 'classic-red') === $preset['id'])>{{ $preset['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="admin-label">Mui ten</label>
                                <select name="design[pointer_preset]" class="admin-soft-input">
                                    @foreach ($pointerPresets as $preset)
                                        <option value="{{ $preset['id'] }}" @selected(($design['pointer_preset'] ?? 'teardrop-gold') === $preset['id'])>{{ $preset['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="admin-label">Center label</label>
                                <input name="design[center_label]" value="{{ $design['center_label'] ?? '19T' }}" x-model="design.center_label" class="admin-soft-input">
                            </div>
                            <div>
                                <label class="admin-label">Background style</label>
                                <select name="design[background_style]" class="admin-soft-input">
                                    @foreach (['warm_gradient', 'soft_purple', 'pastel_grass'] as $style)
                                        <option value="{{ $style }}" @selected(($design['background_style'] ?? 'warm_gradient') === $style)>{{ $style }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="admin-label">Preview note</label>
                                <input name="design[preview_note]" value="{{ $design['preview_note'] ?? '' }}" class="admin-soft-input">
                            </div>
                        </div>
                    </section>
                @endif

                @if ($step === 'publish')
                    <section class="space-y-6">
                        <div class="admin-panel p-6">
                            <h3 class="text-xl font-semibold text-slate-900">4. Thiet ke game</h3>
                            <p class="mt-2 text-sm text-slate-500">Hoan thien copy hien thi, field form va redirect sau khi nhan thuong.</p>
                            <div class="mt-6 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="admin-label">Title</label>
                                    <input name="presentation[title]" value="{{ $presentation['title'] ?? '' }}" x-model="presentation.title" class="admin-soft-input">
                                </div>
                                <div>
                                    <label class="admin-label">Subtitle</label>
                                    <input name="presentation[subtitle]" value="{{ $presentation['subtitle'] ?? '' }}" x-model="presentation.subtitle" class="admin-soft-input">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="admin-label">Description</label>
                                    <textarea name="presentation[description]" rows="3" x-model="presentation.description" class="admin-soft-input">{{ $presentation['description'] ?? '' }}</textarea>
                                </div>
                                <div>
                                    <label class="admin-label">Spin button</label>
                                    <input name="presentation[spin_button]" value="{{ $presentation['spin_button'] ?? '' }}" x-model="presentation.spin_button" class="admin-soft-input">
                                </div>
                                <div>
                                    <label class="admin-label">Continue button</label>
                                    <input name="presentation[continue_button]" value="{{ $presentation['continue_button'] ?? '' }}" class="admin-soft-input">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="admin-label">Loading message</label>
                                    <input name="presentation[loading_message]" value="{{ $presentation['loading_message'] ?? '' }}" class="admin-soft-input">
                                </div>
                            </div>
                        </div>

                        <div class="admin-panel p-6">
                            <h3 class="text-lg font-semibold text-slate-900">Form hien thi</h3>
                            <div class="mt-6 space-y-4">
                                @foreach (collect($presentation['fields'] ?? [])->concat([[
                                    'id' => null,
                                    'field_key' => '',
                                    'type' => 'text',
                                    'label' => '',
                                    'placeholder' => '',
                                    'help_text' => '',
                                    'is_required' => false,
                                    'is_active' => true,
                                    'options' => [],
                                ]]) as $index => $field)
                                    <div class="rounded-3xl border border-slate-200 p-4">
                                        <input type="hidden" name="presentation[fields][{{ $index }}][id]" value="{{ $field['id'] }}">
                                        <div class="mb-4 flex items-center justify-between gap-3">
                                            <div>
                                                <div class="font-semibold text-slate-900">
                                                    {{ $field['id'] ? ($field['field_key'] ?: 'custom_field') : 'Them field moi' }}
                                                </div>
                                                <div class="text-xs uppercase tracking-wide text-slate-500">
                                                    {{ $field['id'] ? ($field['type'] ?: 'text') : 'new field' }}
                                                </div>
                                            </div>
                                            <div class="flex gap-4 text-sm">
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox" name="presentation[fields][{{ $index }}][is_required]" value="1" @checked($field['is_required'] ?? false) class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                                    Required
                                                </label>
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox" name="presentation[fields][{{ $index }}][is_active]" value="1" @checked($field['is_active'] ?? false) class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                                    Active
                                                </label>
                                                @if ($field['id'])
                                                    <label class="inline-flex items-center gap-2 text-rose-600">
                                                        <input type="checkbox" name="presentation[fields][{{ $index }}][remove]" value="1" class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                                                        Xoa
                                                    </label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="admin-label">Field key</label>
                                                <input name="presentation[fields][{{ $index }}][field_key]" value="{{ $field['field_key'] ?? '' }}" class="admin-soft-input" placeholder="vi_du: phone">
                                            </div>
                                            <div>
                                                <label class="admin-label">Type</label>
                                                <select name="presentation[fields][{{ $index }}][type]" class="admin-soft-input">
                                                    @foreach (['text', 'tel', 'select'] as $fieldType)
                                                        <option value="{{ $fieldType }}" @selected(($field['type'] ?? 'text') === $fieldType)>{{ $fieldType }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="admin-label">Label</label>
                                                <input name="presentation[fields][{{ $index }}][label]" value="{{ $field['label'] ?? '' }}" class="admin-soft-input">
                                            </div>
                                            <div>
                                                <label class="admin-label">Placeholder</label>
                                                <input name="presentation[fields][{{ $index }}][placeholder]" value="{{ $field['placeholder'] ?? '' }}" class="admin-soft-input">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="admin-label">Help text</label>
                                                <input name="presentation[fields][{{ $index }}][help_text]" value="{{ $field['help_text'] ?? '' }}" class="admin-soft-input">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="admin-label">Options</label>
                                                <textarea name="presentation[fields][{{ $index }}][options]" rows="3" class="admin-soft-input" placeholder="Moi dong 1 option, dung cho field type select">{{ collect($field['options'] ?? [])->implode("\n") }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="admin-panel p-6">
                            <h3 class="text-lg font-semibold text-slate-900">Redirect sau khi nhan thuong</h3>
                            <div class="mt-6 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="admin-label">Action</label>
                                    <input name="presentation[redirect][action]" value="{{ data_get($presentation, 'redirect.action') }}" class="admin-soft-input">
                                </div>
                                <div>
                                    <label class="admin-label">Target type</label>
                                    <input name="presentation[redirect][target_type]" value="{{ data_get($presentation, 'redirect.target_type') }}" class="admin-soft-input">
                                </div>
                                <div>
                                    <label class="admin-label">Target value</label>
                                    <input name="presentation[redirect][target_value]" value="{{ data_get($presentation, 'redirect.target_value') }}" class="admin-soft-input">
                                </div>
                                <div>
                                    <label class="admin-label">Fallback value</label>
                                    <input name="presentation[redirect][fallback_value]" value="{{ data_get($presentation, 'redirect.fallback_value') }}" class="admin-soft-input">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="admin-label">OA message</label>
                                    <textarea name="presentation[redirect][message_template]" rows="3" class="admin-soft-input" placeholder="Xin chao, toi muon nhan qua tu mini app">{{ data_get($presentation, 'redirect.message_template') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-slate-500">
                        Buoc hien tai: <span class="font-semibold text-slate-700">{{ $steps[$step] }}</span>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @php
                            $stepKeys = array_keys($steps);
                            $currentIndex = array_search($step, $stepKeys, true);
                            $previousStep = $currentIndex > 0 ? $stepKeys[$currentIndex - 1] : null;
                        @endphp
                        @if ($previousStep)
                            <a href="{{ route('games.edit', ['game' => $game, 'step' => $previousStep]) }}" class="admin-secondary-btn">Quay lai</a>
                        @endif
                        <button type="submit" name="intent" value="save" class="admin-secondary-btn">Luu ngay</button>
                        @if ($step !== 'publish')
                            <button type="submit" name="intent" value="continue" class="admin-accent-btn">Tiep tuc</button>
                        @endif
                        @if ($step === 'publish')
                            <button type="submit" name="intent" value="regenerate_launch" class="admin-secondary-btn">Tao lai link</button>
                            @if ($isPublished)
                                <button type="submit" name="intent" value="unpublish" class="admin-secondary-btn">Unpublish</button>
                            @else
                                <button type="submit" name="intent" value="publish" class="admin-primary-btn">Publish game</button>
                            @endif
                        @endif
                    </div>
                </div>
            </form>

            <aside class="space-y-6">
                <div class="admin-panel overflow-hidden">
                    <div class="admin-panel-header">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Xem truoc</h3>
                            <p class="mt-1 text-sm text-slate-500">Preview nhanh theo draft hien tai.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-500">
                            {{ $isPublished ? 'published' : 'draft' }}
                        </span>
                    </div>
                    <div class="p-6">
                        <div class="rounded-[32px] bg-[#eef2f7] p-6">
                            <div class="text-center">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-500" x-text="presentation.subtitle || 'Uong an lanh, gop ngan'"></p>
                                <h4 class="mt-3 text-5xl font-light italic text-rose-500" x-text="presentation.title || 'Yeu Thuong'"></h4>
                            </div>

                            <div class="relative mx-auto mt-8 aspect-square w-full max-w-[440px]">
                                <div class="absolute inset-0 rounded-full p-3 shadow-2xl" :style="`background: linear-gradient(135deg, ${design.accent_color || '#d79e2f'}, ${design.primary_color || '#f9c667'})`">
                                    <div class="relative h-full w-full overflow-hidden rounded-full border-[10px] border-white bg-white">
                                        <div class="absolute inset-0 rounded-full" :style="`background: conic-gradient(${(preview.slice_colors || ['#fdf1d0','#ffcf64','#ff914d','#ff5040']).map((color, index, all) => `${color} ${(360 / all.length) * index}deg ${(360 / all.length) * (index + 1)}deg`).join(', ')})`"></div>

                                        <template x-for="(reward, index) in (rewards.prizes || []).filter(item => item.label || item.code)" :key="index">
                                            <div
                                                class="absolute left-1/2 top-1/2 w-28 -translate-x-1/2 -translate-y-1/2 text-center text-xs font-semibold text-slate-700"
                                                :style="`transform: translate(-50%, -50%) rotate(${index * (360 / Math.max((rewards.prizes || []).filter(item => item.label || item.code).length, 1))}deg) translateY(-140px);`"
                                            >
                                                <div class="rounded-2xl bg-white/80 px-3 py-2 shadow-sm backdrop-blur" x-text="reward.label || reward.code"></div>
                                            </div>
                                        </template>

                                        <div class="absolute left-1/2 top-1/2 grid h-24 w-24 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white shadow-xl">
                                            <div class="grid h-16 w-16 place-items-center rounded-full text-lg font-black text-rose-500" :style="`background: ${design.primary_color || '#f9c667'}`">
                                                <span x-text="design.center_label || '19T'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="absolute left-1/2 top-0 h-0 w-0 -translate-x-1/2 border-l-[18px] border-r-[18px] border-t-[44px] border-l-transparent border-r-transparent border-t-amber-400"></div>
                            </div>

                            <div class="mt-8 text-center">
                                <button class="rounded-2xl bg-purple-700 px-8 py-3 text-base font-semibold text-white shadow-lg" type="button" x-text="presentation.spin_button || 'Quay ngay'"></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-panel p-6">
                    <h3 class="text-lg font-semibold text-slate-900">Publish state</h3>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <div class="flex items-center justify-between">
                            <span>Trang thai</span>
                            <span class="font-semibold uppercase">{{ $isPublished ? 'published' : 'draft' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Last saved</span>
                            <span class="font-semibold">{{ optional($builderConfig->last_saved_at)->diffForHumans() ?? 'Chua luu' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Published</span>
                            <span class="font-semibold">{{ optional($builderConfig->published_at)->diffForHumans() ?? 'Chua publish' }}</span>
                        </div>
                    </div>
                </div>

                <div class="admin-panel p-6">
                    <h3 class="text-lg font-semibold text-slate-900">Launch links</h3>
                    <div class="mt-4 space-y-4 text-sm text-slate-600">
                        <div>
                            <div class="font-medium text-slate-500">Public ID</div>
                            <div class="mt-1 flex gap-2">
                                <input id="legacy-launch-public-id" value="{{ $launchData['public_id'] }}" readonly class="admin-soft-input">
                                <button type="button" class="admin-secondary-btn" onclick="navigator.clipboard.writeText(document.getElementById('legacy-launch-public-id').value)">Copy</button>
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-500">Trang thai launch</div>
                            <div class="mt-1 rounded-2xl bg-slate-50 px-4 py-3 font-semibold text-slate-700">{{ $launchData['status'] }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-500">Link xem truoc</div>
                            <div class="mt-1 flex gap-2">
                                <input id="legacy-launch-preview-url" value="{{ $launchData['preview_url'] }}" readonly class="admin-soft-input">
                                <button type="button" class="admin-secondary-btn" onclick="navigator.clipboard.writeText(document.getElementById('legacy-launch-preview-url').value)">Copy</button>
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-500">Mini App path</div>
                            <div class="mt-1 flex gap-2">
                                <input id="legacy-launch-miniapp-path" value="{{ $launchData['miniapp_path'] }}" readonly class="admin-soft-input">
                                <button type="button" class="admin-secondary-btn" onclick="navigator.clipboard.writeText(document.getElementById('legacy-launch-miniapp-path').value)">Copy</button>
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-500">Link mo trong Zalo</div>
                            <div class="mt-1 flex gap-2">
                                <input id="legacy-launch-zalo-url" value="{{ $launchData['zalo_url'] }}" readonly class="admin-soft-input">
                                <button type="button" class="admin-secondary-btn" onclick="navigator.clipboard.writeText(document.getElementById('legacy-launch-zalo-url').value)">Copy</button>
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-500">QR payload</div>
                            <div class="mt-1 flex gap-2">
                                <input id="legacy-launch-qr-payload" value="{{ $launchData['qr_payload'] }}" readonly class="admin-soft-input">
                                <button type="button" class="admin-secondary-btn" onclick="navigator.clipboard.writeText(document.getElementById('legacy-launch-qr-payload').value)">Copy</button>
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-500">Ma QR</div>
                            @if ($launchData['qr_preview_url'] !== '')
                                <div class="mt-2 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <img src="{{ $launchData['qr_preview_url'] }}" alt="QR launch link" class="mx-auto h-56 w-56 rounded-2xl object-contain">
                                    <p class="mt-3 text-center text-xs text-slate-500">Quet ma nay de mo link launch hien tai.</p>
                                </div>
                            @else
                                <div class="mt-2 rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                                    Chua co du lieu QR de hien thi.
                                </div>
                            @endif
                        </div>
                        <div class="rounded-2xl bg-amber-50 px-4 py-3 text-amber-800">
                            {{ $launchData['message'] }}
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
