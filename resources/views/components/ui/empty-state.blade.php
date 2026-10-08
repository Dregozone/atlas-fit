@props([
    'icon' => 'sparkles',
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-zinc-300 px-6 py-10 text-center dark:border-white/15') }}>
    <div class="flex size-12 animate-float items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400" aria-hidden="true">
        <flux:icon :icon="$icon" class="size-6" />
    </div>
    <div>
        <p class="font-semibold text-zinc-900 dark:text-white">{{ $title }}</p>
        @if($description)
            <flux:text class="mt-1 max-w-sm">{{ $description }}</flux:text>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="mt-1">{{ $slot }}</div>
    @endif
</div>
