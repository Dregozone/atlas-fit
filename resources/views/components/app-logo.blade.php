@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Atlas Fit" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-xl bg-linear-to-br from-emerald-400 to-cyan-500 shadow-md shadow-emerald-500/30">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Atlas Fit" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-xl bg-linear-to-br from-emerald-400 to-cyan-500 shadow-md shadow-emerald-500/30">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </x-slot>
    </flux:brand>
@endif
