{{-- Animated inline confirmation shown after a successful action. --}}
@props([
    'title' => null,
])

<div {{ $attributes->class('flex animate-pop items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100') }} role="status">
    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white" aria-hidden="true">
        <flux:icon.check variant="mini" class="size-5" />
    </div>
    <div class="min-w-0 text-sm">
        @if($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <p>{{ $slot }}</p>
    </div>
</div>
