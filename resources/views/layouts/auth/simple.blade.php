<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
        <div class="af-hero pointer-events-none fixed inset-0 -z-10" aria-hidden="true"></div>
        <main class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm animate-fade-up flex-col gap-2">
                <a href="{{ route('home') }}" class="mb-2 flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-linear-to-br from-emerald-400 to-cyan-500 shadow-lg shadow-emerald-500/30">
                        <x-app-logo-icon class="size-7 fill-current text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6 rounded-3xl border border-zinc-200 bg-white/80 p-6 shadow-xl shadow-zinc-900/5 backdrop-blur-xl sm:p-8 dark:border-white/10 dark:bg-zinc-900/70">
                    {{ $slot }}
                </div>
            </div>
        </main>
        @fluxScripts
    </body>
</html>
