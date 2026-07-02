@php
    $fieldWrapperView = $getFieldWrapperView();
    $targetStatePath = $targetStatePath ?? $getStatePath();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        x-data="{ value: $wire.entangle('{{ $targetStatePath }}').live }"
        style="display: grid; gap: 12px;"
    >
        <div
            style="
                display: grid;
                gap: 12px;
                grid-template-columns: repeat({{ $variant === 'pointer' ? 3 : 4 }}, minmax(0, 1fr));
                align-items: start;
            "
        >
            @foreach ($options as $id => $option)
                @php
                    $isPalette = $variant === 'palette';
                    $isPointer = $variant === 'pointer';
                    $isBackground = $variant === 'background';
                    $asset = $option['asset'] ?? null;
                    $assetSrc = $asset ? \App\Filament\Resources\GameResource::wheelAssetDataUri($asset) : null;
                    $shape = $option['shape'] ?? null;
                    $background = $option['background'] ?? null;
                    $overlay = $option['overlay'] ?? null;
                @endphp

                <button
                    type="button"
                    x-on:click="value = @js($id); $wire.set(@js($targetStatePath), @js($id), true)"
                    x-bind:class="value === '{{ $id }}' ? 'ring-2 ring-orange-500 border-orange-400' : 'border-slate-200 hover:border-slate-300'"
                    class="border-slate-200 hover:border-slate-300 rounded-2xl border bg-white p-3 text-left shadow-sm transition focus:outline-none"
                    style="
                        width: 100%;
                        min-width: 0;
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        align-items: stretch;
                        justify-content: flex-start;
                    "
                >
                    @if ($isPalette)
                        <div
                            class="rounded-xl bg-slate-100"
                            style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); overflow: hidden; height: 52px;"
                        >
                            @foreach ($option['colors'] as $color)
                                <span style="display: block; height: 52px; background: {{ $color }};"></span>
                            @endforeach
                        </div>
                    @elseif ($isPointer)
                        <div
                            class="rounded-xl bg-slate-50"
                            style="display: flex; height: 120px; align-items: center; justify-content: center;"
                        >
                            <span
                                style="
                                    display: block;
                                    width: 48px;
                                    height: 68px;
                                    filter: drop-shadow(0 8px 14px rgba(0, 0, 0, 0.14));
                                    clip-path: {{ $shape }};
                                    background: {{ $background }};
                                "
                            ></span>
                        </div>
                    @elseif ($isBackground)
                        <div
                            class="rounded-xl bg-slate-50"
                            style="display: flex; align-items: center; justify-content: center; padding: 8px; height: 96px; overflow: hidden;"
                        >
                            @if ($assetSrc)
                                <img
                                    src="{{ $assetSrc }}"
                                    alt="{{ $option['label'] }}"
                                    style="display: block; width: 100%; height: 100%; object-fit: cover; border-radius: 14px;"
                                />
                            @else
                                <div
                                    style="
                                        width: 100%;
                                        height: 100%;
                                        border-radius: 14px;
                                        background:
                                            {{ $overlay ? $overlay.',' : '' }}
                                            {{ $background }};
                                    "
                                ></div>
                            @endif
                        </div>
                    @elseif ($assetSrc)
                        <div
                            class="rounded-xl bg-slate-50"
                            style="display: flex; align-items: center; justify-content: center; padding: 8px; height: 132px;"
                        >
                            <img
                                src="{{ $assetSrc }}"
                                alt="{{ $option['label'] }}"
                                style="display: block; width: 100%; max-width: 120px; max-height: 116px; object-fit: contain;"
                            />
                        </div>
                    @endif

                    <div
                        class="text-sm font-semibold text-slate-800"
                        style="text-align: center; line-height: 1.35; min-height: 38px;"
                    >
                        {{ $option['label'] }}
                    </div>
                </button>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
