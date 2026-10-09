<?php

use App\Models\Day;
use App\Models\Rotation;
use App\Models\WorkoutSession;
use App\Models\Workout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Schedule')] class extends Component {

    #[Computed]
    public function rotations()
    {
        return Rotation::orderBy('week')->get();
    }

    #[Computed]
    public function days()
    {
        return Day::all()->keyBy('day');
    }

    #[Computed]
    public function sessions()
    {
        return WorkoutSession::all()->keyBy('session');
    }

    #[Computed]
    public function workoutsBySession(): array
    {
        $indexed = [];
        foreach (Workout::orderBy('exercise_no')->get() as $workout) {
            $indexed[$workout->session][] = $workout;
        }

        return $indexed;
    }
};
?>

<div class="af-stagger flex flex-col gap-6">
    @php
        $currentRotationWeek = ((int) now()->format('W') % 3) + 1;
        $todayName = now()->format('l');
        $rotationStyles = [
            1 => 'from-rose-600 to-orange-600',
            2 => 'from-sky-600 to-indigo-600',
            3 => 'from-emerald-600 to-teal-700',
        ];
    @endphp

    <x-ui.page-header icon="calendar-days" eyebrow="Plan" title="Programme Schedule" subtitle="3-week rotating programme. Each week has a different intensity." />

    {{-- 3-Week Rotation Overview --}}
    <section aria-labelledby="rotation-heading">
        <h2 id="rotation-heading" class="sr-only">3-week rotation</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach($this->rotations as $rotation)
                @php $isCurrent = $rotation->week === $currentRotationWeek; @endphp
                <div class="relative overflow-hidden rounded-2xl bg-linear-to-br p-5 text-white shadow-lg {{ $rotationStyles[$rotation->week] ?? 'from-zinc-600 to-zinc-800' }} {{ $isCurrent ? 'ring-4 ring-emerald-300/70 dark:ring-emerald-400/40' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-white/90">Week {{ $rotation->week }}</p>
                            <p class="text-2xl font-bold">{{ $rotation->program }}</p>
                        </div>
                        @if($isCurrent)
                            <span class="rounded-full bg-white/25 px-2.5 py-1 text-xs font-semibold backdrop-blur">This week</span>
                        @endif
                    </div>
                    <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
                        @foreach(['Sets' => $rotation->sets, 'Reps' => $rotation->reps, '1RM' => $rotation->weight_percent.'%'] as $label => $value)
                            <div class="flex flex-col-reverse rounded-xl bg-black/20 p-2">
                                <dt class="text-[11px] uppercase tracking-wide text-white/90">{{ $label }}</dt>
                                <dd class="text-xl font-bold tabular-nums">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Weekly Day Breakdown --}}
    <flux:card class="!rounded-2xl">
        <flux:heading size="lg" level="2" class="mb-4">Weekly Day Plan</flux:heading>
        <ol class="-mx-2 flex snap-x gap-3 overflow-x-auto px-2 pb-2 lg:grid lg:grid-cols-7 lg:overflow-visible">
            @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $dayName)
                @php
                    $day = $this->days->get($dayName);
                    $sessionKey = $day?->session ?? '';
                    $session = $sessionKey ? $this->sessions->get($sessionKey) : null;
                    $isToday = $dayName === $todayName;
                @endphp
                <li class="flex min-w-36 snap-start flex-col gap-2 rounded-2xl border p-4 lg:min-w-0 {{ $isToday ? 'border-emerald-400 bg-emerald-50 dark:border-emerald-500/50 dark:bg-emerald-500/10' : 'border-zinc-200 dark:border-white/10' }}"
                    @if($isToday) aria-current="date" @endif>
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold">{{ $dayName }}</p>
                        @if($isToday)
                            <span class="size-2 animate-pulse rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <span class="sr-only">(today)</span>
                        @endif
                    </div>
                    @if($sessionKey)
                        <flux:badge color="emerald" size="sm" class="self-start">Session {{ strtoupper($sessionKey) }}</flux:badge>
                        <p class="text-sm font-medium">{{ $session?->primary_muscle_group ?? '—' }}</p>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">+ {{ $session?->secondary_muscle_group ?? '—' }}</p>
                    @else
                        <flux:badge color="zinc" size="sm" icon="moon" class="self-start">Rest</flux:badge>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Recover &amp; recharge</p>
                    @endif
                </li>
            @endforeach
        </ol>
    </flux:card>

    {{-- Session Details --}}
    <div class="grid gap-6 xl:grid-cols-3">
        @foreach($this->sessions as $sessionKey => $session)
            @php
                $primaryExercises = $this->workoutsBySession[$session->primary_muscle_group] ?? [];
                $secondaryExercises = $this->workoutsBySession[$session->secondary_muscle_group] ?? [];
            @endphp
            <flux:card class="!rounded-2xl">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-emerald-400 to-cyan-500 text-lg font-bold uppercase text-white shadow-md shadow-emerald-500/25" aria-hidden="true">{{ $sessionKey }}</span>
                    <div>
                        <flux:heading size="lg" level="2">Session {{ strtoupper($sessionKey) }}</flux:heading>
                        <flux:text class="text-sm">Primary: {{ $session->primary_muscle_group }} · Secondary: {{ $session->secondary_muscle_group }}</flux:text>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach(['Primary Exercises' => $primaryExercises, 'Secondary Exercises' => $secondaryExercises] as $listLabel => $exercises)
                        @if(count($exercises))
                            <div>
                                <h3 class="mb-2 text-xs font-semibold uppercase tracking-widest text-zinc-600 dark:text-zinc-400">{{ $listLabel }}</h3>
                                <ol class="divide-y divide-zinc-100 rounded-xl border border-zinc-200 dark:divide-white/5 dark:border-white/10">
                                    @foreach($exercises as $ex)
                                        <li class="flex items-center gap-3 px-3 py-2 text-sm">
                                            <span class="w-5 text-center text-xs font-semibold tabular-nums text-zinc-500 dark:text-zinc-400">{{ $ex->exercise_no }}</span>
                                            <span class="flex-1">{{ $ex->equipment }}</span>
                                            <span class="text-xs tabular-nums text-zinc-600 dark:text-zinc-400">{{ $ex->weight_1rm ? $ex->weight_1rm . '% 1RM' : '—' }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    @endforeach
                </div>
            </flux:card>
        @endforeach
    </div>

</div>
