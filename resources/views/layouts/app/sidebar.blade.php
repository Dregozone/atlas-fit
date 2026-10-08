@php
    $primaryNavigation = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'home', 'label' => __('Today'), 'short' => __('Today')],
        ['route' => 'schedule', 'match' => 'schedule', 'icon' => 'calendar-days', 'label' => __('Schedule'), 'short' => __('Plan')],
        ['route' => 'workouts', 'match' => 'workouts', 'icon' => 'bolt', 'label' => __('Workouts'), 'short' => __('Train')],
        ['route' => 'nutrition', 'match' => 'nutrition', 'icon' => 'fire', 'label' => __('Nutrition'), 'short' => __('Fuel')],
        ['route' => 'weight', 'match' => 'weight*', 'icon' => 'scale', 'label' => __('Weight'), 'short' => __('Weight')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100" data-af-app>
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-emerald-500 focus:px-4 focus:py-2 focus:font-semibold focus:text-zinc-950">
            {{ __('Skip to main content') }}
        </a>

        {{-- Ambient brand glow --}}
        <div class="af-hero pointer-events-none fixed inset-x-0 top-0 -z-10 h-[28rem] opacity-60 dark:opacity-100" aria-hidden="true"></div>

        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white/80 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-900/80">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Your training')" class="grid">
                    @foreach($primaryNavigation as $item)
                        <flux:sidebar.item :icon="$item['icon']" :href="route($item['route'])" :current="request()->routeIs($item['match'])" wire:navigate>
                            {{ $item['label'] }}
                        </flux:sidebar.item>
                    @endforeach
                </flux:sidebar.group>

                @if(auth()->user()?->is_admin)
                <flux:sidebar.group :heading="__('Admin')" class="grid">
                    <flux:sidebar.item icon="adjustments-horizontal" :href="route('admin.schedule')" :current="request()->routeIs('admin.*')" wire:navigate>
                        {{ __('Manage Schedule') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit', 'settings.*')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile Header -->
        <flux:header class="sticky top-0 z-10 border-b border-zinc-200 bg-white/80 backdrop-blur-xl lg:hidden dark:border-white/10 dark:bg-zinc-900/80">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" :aria-label="__('Open menu')" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate class="ms-2" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                    :aria-label="__('Account menu')"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <!-- Mobile bottom tab bar -->
        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden dark:border-white/10 dark:bg-zinc-900/90" aria-label="{{ __('Primary') }}">
            <ul class="mx-auto grid max-w-lg grid-cols-5">
                @foreach($primaryNavigation as $item)
                    @php $isCurrent = request()->routeIs($item['match']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}" wire:navigate
                            @if($isCurrent) aria-current="page" @endif
                            class="group flex min-h-16 flex-col items-center justify-center gap-1 text-[11px] font-medium transition focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-emerald-500 {{ $isCurrent ? 'text-emerald-700 dark:text-emerald-400' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
                        >
                            <span class="flex h-8 w-12 items-center justify-center rounded-full transition {{ $isCurrent ? 'bg-emerald-100 dark:bg-emerald-500/15' : 'group-active:scale-90' }}">
                                <flux:icon :icon="$item['icon']" :variant="$isCurrent ? 'solid' : 'outline'" class="size-5" />
                            </span>
                            {{ $item['short'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <!-- Celebration toast: shown whenever a component dispatches `celebrate` -->
        <div
            x-data="{ show: false, message: '', timer: null }"
            x-on:celebrate.window="
                const detail = Array.isArray($event.detail) ? ($event.detail[0] ?? {}) : ($event.detail ?? {});
                message = detail.message ?? @js(__('Nice work!'));
                show = true;
                clearTimeout(timer);
                timer = setTimeout(() => show = false, 3200);
            "
            class="pointer-events-none fixed inset-x-0 bottom-24 z-50 flex justify-center px-4 lg:bottom-8"
            aria-live="polite"
        >
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 translate-y-2"
                x-cloak
                class="pointer-events-auto flex items-center gap-3 rounded-full bg-zinc-900 py-2 ps-2 pe-5 text-sm font-medium text-white shadow-2xl shadow-emerald-500/20 ring-1 ring-white/10 dark:bg-white dark:text-zinc-900"
            >
                <span class="flex size-8 items-center justify-center rounded-full bg-linear-to-br from-emerald-400 to-cyan-500 text-white" aria-hidden="true">
                    <flux:icon.sparkles variant="mini" class="size-4" />
                </span>
                <span x-text="message"></span>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
