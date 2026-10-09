@props([
    'label',
    'value',
    'icon' => null,
    'hint' => null,
    'tone' => 'emerald',
])

@php
    $toneClasses = match ($tone) {
        'sky' => 'from-sky-400 to-blue-500 shadow-sky-500/25',
        'amber' => 'from-amber-400 to-orange-500 shadow-amber-500/25',
        'violet' => 'from-violet-400 to-fuchsia-500 shadow-violet-500/25',
        'rose' => 'from-rose-400 to-pink-500 shadow-rose-500/25',
        default => 'from-emerald-400 to-teal-500 shadow-emerald-500/25',
    };
    $isInteractive = $attributes->has('href') || $attributes->has('wire:click');
    $tag = $attributes->has('href') ? 'a' : ($attributes->has('wire:click') ? 'button' : 'div');
@endphp

<{{ $tag }} {{ $attributes->class([
    'group relative flex w-full items-center gap-3 overflow-hidden rounded-2xl border border-zinc-200 bg-white p-4 text-start dark:border-white/10 dark:bg-white/5',
    'transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-zinc-900/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 cursor-pointer' => $isInteractive,
]) }} @if($tag === 'button') type="button" @endif>
    @if($icon)
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br text-white shadow-lg {{ $toneClasses }}" aria-hidden="true">
            <flux:icon :icon="$icon" variant="solid" class="size-5" />
        </div>
    @endif
    <div class="min-w-0">
        <p class="truncate text-2xl font-bold tabular-nums tracking-tight text-zinc-900 dark:text-white">{{ $value }}</p>
        <p class="text-sm leading-snug text-zinc-600 dark:text-zinc-400">{{ $label }}</p>
        @if($hint)
            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
        @endif
    </div>
    @if($isInteractive)
        <flux:icon.chevron-right class="ms-auto size-4 shrink-0 text-zinc-400 transition group-hover:translate-x-0.5" aria-hidden="true" />
    @endif
</{{ $tag }}>
