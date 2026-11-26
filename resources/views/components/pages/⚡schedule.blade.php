<?php

use App\Models\Rotation;
use App\Models\UserDay;
use App\Models\Workout;
use App\Models\WorkoutSession;
use App\Services\UserSchedule;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Schedule')] class extends Component {

    /** Sentinel select value for a rest day (an empty value would read as "not chosen"). */
    public const REST_DAY = 'rest';

    public bool $isEditing = false;

    /** @var array<string, string> The session key (or "rest") chosen for each weekday while editing. */
    public array $dayPlan = [];

    public bool $showResetConfirmation = false;

    #[Computed]
    public function schedule(): UserSchedule
    {
        return new UserSchedule(auth()->user());
    }

    #[Computed]
    public function rotations()
    {
        return Rotation::orderBy('week')->get();
    }

    #[Computed]
    public function sessions()
    {
        return WorkoutSession::orderBy('session')->get()->keyBy('session');
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

    public function startEditing(): void
    {
        $this->dayPlan = collect(UserSchedule::WEEKDAYS)
            ->mapWithKeys(fn (string $dayName) => [$dayName => $this->schedule->sessionKeyFor($dayName) ?? self::REST_DAY])
            ->all();

        $this->isEditing = true;
    }

    public function stopEditing(): void
    {
        $this->isEditing = false;
    }

    /**
     * Saves the session chosen for a weekday. Choosing the programme's own session removes the change.
     */
    public function updatedDayPlan(mixed $value, string $dayName): void
    {
        if (! in_array($dayName, UserSchedule::WEEKDAYS, true)) {
            unset($this->dayPlan[$dayName]);

            return;
        }

        $this->validate([
            'dayPlan.'.$dayName => ['required', Rule::in([self::REST_DAY, ...$this->sessions->keys()->all()])],
        ]);

        $sessionKey = $value === self::REST_DAY ? null : $value;

        if ($sessionKey === $this->schedule->programmeSessionKeyFor($dayName)) {
            UserDay::where('user_id', auth()->id())->where('day', $dayName)->delete();
        } else {
            UserDay::updateOrCreate(
                ['user_id' => auth()->id(), 'day' => $dayName],
                ['session' => $sessionKey],
            );
        }

        unset($this->schedule);
    }

    /**
     * Switches a programme exercise on or off for the signed-in user only.
     */
    public function toggleExercise(int $workoutId): void
    {
        $workout = Workout::findOrFail($workoutId);

        auth()->user()->excludedWorkouts()->toggle($workout->id);

        unset($this->schedule);
    }

    /**
     * Removes all of the user's changes so they follow the shared programme again.
     */
    public function resetToProgramme(): void
    {
        $user = auth()->user();
        $user->excludedWorkouts()->detach();
        $user->scheduleDays()->delete();

        unset($this->schedule);
        $this->showResetConfirmation = false;

        if ($this->isEditing) {
            $this->startEditing();
        }

        $this->dispatch('celebrate', message: 'Schedule reset to the programme.');
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

    <x-ui.page-header icon="calendar-days" eyebrow="Plan" title="Programme Schedule" subtitle="3-week rotating programme. Each week has a different intensity.">
        <x-slot:actions>
            @if($this->schedule->hasCustomisations())
                <flux:button variant="ghost" icon="arrow-uturn-left" wire:click="$set('showResetConfirmation', true)">Reset to programme</flux:button>
            @endif
            @if($isEditing)
                <flux:button variant="primary" icon="check" wire:click="stopEditing">Done</flux:button>
            @else
                <flux:button variant="primary" icon="pencil-square" wire:click="startEditing">Edit my schedule</flux:button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if($isEditing)
        <flux:callout icon="adjustments-horizontal" color="emerald">
            <flux:callout.heading>Make the programme yours</flux:callout.heading>
            <flux:callout.text>Choose the session (or a rest day) for each day, and switch off exercises you can't do, for example ones that need gym equipment when you train at home. Changes save straight away and only affect you. They show on your dashboard too.</flux:callout.text>
        </flux:callout>
    @endif

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
        <flux:heading size="lg" level="2" class="mb-4">{{ $this->schedule->hasCustomisations() ? 'Your Weekly Day Plan' : 'Weekly Day Plan' }}</flux:heading>
        <ol class="-mx-2 flex snap-x gap-3 overflow-x-auto px-2 pb-2 lg:grid lg:grid-cols-7 lg:overflow-visible">
            @foreach(\App\Services\UserSchedule::WEEKDAYS as $dayName)
                @php
                    $sessionKey = $this->schedule->sessionKeyFor($dayName) ?? '';
                    $session = $sessionKey ? $this->sessions->get($sessionKey) : null;
                    $isToday = $dayName === $todayName;
                    $isCustomDay = $this->schedule->hasCustomDay($dayName);
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
                    @if($isEditing)
                        <flux:select size="sm" wire:model.live="dayPlan.{{ $dayName }}" :aria-label="'Session for '.$dayName">
                            <flux:select.option value="{{ $this::REST_DAY }}">Rest</flux:select.option>
                            @foreach($this->sessions as $optionKey => $optionSession)
                                <flux:select.option value="{{ $optionKey }}">{{ strtoupper($optionKey) }}: {{ $optionSession->primary_muscle_group }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                    @if($isCustomDay)
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">Changed by you</span>
                    @endif
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
                            @php
                                $includedExercises = array_values(array_filter($exercises, fn ($ex) => ! $this->schedule->isExcluded($ex)));
                                $switchedOffCount = count($exercises) - count($includedExercises);
                                $listedExercises = $isEditing ? $exercises : $includedExercises;
                            @endphp
                            <div>
                                <h3 class="mb-2 text-xs font-semibold uppercase tracking-widest text-zinc-600 dark:text-zinc-400">{{ $listLabel }}</h3>
                                @if(count($listedExercises))
                                    <ol class="divide-y divide-zinc-100 rounded-xl border border-zinc-200 dark:divide-white/5 dark:border-white/10">
                                        @foreach($listedExercises as $ex)
                                            @php $isIncluded = ! $this->schedule->isExcluded($ex); @endphp
                                            <li wire:key="exercise-{{ $sessionKey }}-{{ $ex->id }}" class="flex items-center gap-3 px-3 py-2 text-sm">
                                                <span class="w-5 text-center text-xs font-semibold tabular-nums text-zinc-500 dark:text-zinc-400">{{ $ex->exercise_no }}</span>
                                                <span class="flex-1 {{ $isIncluded ? '' : 'text-zinc-400 line-through dark:text-zinc-500' }}">{{ $ex->equipment }}</span>
                                                <span class="text-xs tabular-nums text-zinc-600 dark:text-zinc-400">{{ $ex->weight_1rm ? $ex->weight_1rm . '% 1RM' : '—' }}</span>
                                                @if($isEditing)
                                                    <flux:switch :checked="$isIncluded" wire:click="toggleExercise({{ $ex->id }})" :aria-label="'Include '.$ex->equipment" />
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                @else
                                    <p class="rounded-xl border border-dashed border-zinc-200 px-3 py-2 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-400">All exercises switched off.</p>
                                @endif
                                @if(! $isEditing && $switchedOffCount > 0)
                                    <p class="mt-1.5 text-xs text-zinc-600 dark:text-zinc-400">{{ $switchedOffCount }} {{ Str::plural('exercise', $switchedOffCount) }} switched off by you</p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </flux:card>
        @endforeach
    </div>

    <flux:modal wire:model="showResetConfirmation" class="w-full max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Reset to the programme?</flux:heading>
                <flux:text class="mt-2">This switches every exercise back on and puts every day back to the programme's session.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" class="flex-1">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="arrow-uturn-left" class="flex-1" wire:click="resetToProgramme">Reset</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
