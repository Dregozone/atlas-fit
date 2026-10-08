@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'icon' => null,
])

<header {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between') }}>
    <div class="flex items-start gap-4">
        @if($icon)
            <div class="hidden size-12 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-emerald-400 to-cyan-500 text-white shadow-lg shadow-emerald-500/25 sm:flex" aria-hidden="true">
                <flux:icon :icon="$icon" variant="solid" class="size-6" />
            </div>
        @endif
        <div class="min-w-0">
            @if($eyebrow)
                <p class="text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">{{ $eyebrow }}</p>
            @endif
            <flux:heading size="xl" level="1" class="!text-2xl font-bold tracking-tight sm:!text-3xl">{{ $title }}</flux:heading>
            @if($subtitle)
                <flux:text class="mt-1 max-w-2xl">{{ $subtitle }}</flux:text>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>
