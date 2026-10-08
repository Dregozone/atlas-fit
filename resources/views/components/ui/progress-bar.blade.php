@props([
    'percent' => 0,
    'color' => 'bg-emerald-500',
    'label' => null,
    'height' => 'h-2',
])

@php $clamped = max(0, min(100, (float) $percent)); @endphp

<div {{ $attributes->class("w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10 {$height}") }}
    role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($clamped) }}"
    @if($label) aria-label="{{ $label }}" @endif
>
    <div class="af-bar {{ $height }} rounded-full {{ $color }}" style="width: {{ $clamped }}%"></div>
</div>
