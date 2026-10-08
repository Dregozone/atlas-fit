{{-- Circular progress ring. Colour comes from the `text-*` class passed via `color`. --}}
@props([
    'percent' => 0,
    'size' => 96,
    'stroke' => 9,
    'color' => 'text-emerald-500',
    'label' => null,
    'track' => 'stroke-zinc-200 dark:stroke-white/10',
])

@php
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $clamped = max(0, min(100, (float) $percent));
    $offset = $circumference * (1 - $clamped / 100);
@endphp

<div {{ $attributes->class('relative inline-flex shrink-0 items-center justify-center') }}
    style="width: {{ $size }}px; height: {{ $size }}px"
    role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($clamped) }}"
    @if($label) aria-label="{{ $label }}" @endif
>
    <svg viewBox="0 0 {{ $size }} {{ $size }}" class="absolute inset-0 -rotate-90" aria-hidden="true">
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke-width="{{ $stroke }}" class="{{ $track }}" />
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke-width="{{ $stroke }}" stroke-linecap="round"
            stroke="currentColor" class="af-ring-value {{ $color }}"
            stroke-dasharray="{{ $circumference }}"
            style="--af-ring-from: {{ $circumference }}; stroke-dashoffset: {{ $offset }}"
        />
    </svg>
    <div class="relative flex flex-col items-center justify-center text-center leading-tight">
        {{ $slot }}
    </div>
</div>
