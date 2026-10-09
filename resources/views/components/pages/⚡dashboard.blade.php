<?php

use App\Models\Day;
use App\Models\Rotation;
use App\Models\WorkoutSession;
use App\Models\Workout;
use App\Models\Consumed;
use App\Models\Achievement;
use App\Models\CompletedWorkout;
use App\Services\MacroCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {

    public bool $showAchievementsModal = false;

    #[Computed]
    public function todaySchedule(): array
    {
        return $this->buildDaySchedule(Carbon::now());
    }

    #[Computed]
    public function tomorrowSchedule(): array
    {
        return $this->buildDaySchedule(Carbon::now()->addDay());
    }

    private function buildDaySchedule(Carbon $date): array
    {
        $dayName = $date->format('l');
        $weekNumber = (int) $date->format('W');
        $rotationWeek = ($weekNumber % 3) + 1;

        $rotation = Rotation::where('week', $rotationWeek)->first();
        $day = Day::where('day', $dayName)->first();

        if (! $day || empty($day->session)) {
            return [
                'day' => $dayName,
                'rotation' => $rotation,
                'session' => null,
                'primary_exercises' => [],
                'secondary_exercises' => [],
                'is_rest_day' => true,
            ];
        }

        $session = WorkoutSession::where('session', $day->session)->first();
        $primaryExercises = $session
            ? Workout::where('session', $session->primary_muscle_group)->orderBy('exercise_no')->get()
            : collect();
        $secondaryExercises = $session
            ? Workout::where('session', $session->secondary_muscle_group)->orderBy('exercise_no')->get()
            : collect();

        return [
            'day' => $dayName,
            'rotation' => $rotation,
            'session' => $session,
            'primary_exercises' => $primaryExercises,
            'secondary_exercises' => $secondaryExercises,
            'is_rest_day' => false,
        ];
    }

    #[Computed]
    public function personalBests(): array
    {
        // Display name => equipment name as stored against logged workouts.
        $benchmarkLifts = [
            'Overhead press' => '(Ben.) Overhead press',
            'Bench press' => '(Ben.) Bench press',
            'Squat' => '(Ben.) Squat',
            'Deadlift' => '(Ben.) Deadlift',
        ];

        $pbs = CompletedWorkout::where('user_id', auth()->id())
            ->where('is_deleted', false)
            ->whereIn('equipment', $benchmarkLifts)
            ->selectRaw('MAX(weight) AS lbs, equipment')
            ->groupBy('equipment')
            ->pluck('lbs', 'equipment')
            ->toArray();

        return array_map(fn (string $name, string $equipment) => [
            'name' => $name,
            'lbs' => $pbs[$equipment] ?? null,
        ], array_keys($benchmarkLifts), $benchmarkLifts);
    }

    #[Computed]
    public function macroGoals(): array
    {
        $user = auth()->user();

        if (! $user->body_weight_lbs || ! $user->fitness_goal) {
            return ['carbs' => null, 'protein' => null, 'fat' => null, 'calories' => null];
        }

        $calc = new MacroCalculator;
        $calc->setWeightLbs($user->body_weight_lbs);
        $calc->setGoal($user->fitness_goal);
        $calc->findMacros();

        return [
            'carbs' => round($calc->getCarbs()),
            'protein' => round($calc->getProtein()),
            'fat' => round($calc->getFat()),
            'calories' => round($calc->getCalories()),
        ];
    }

    #[Computed]
    public function macrosUsedToday(): object
    {
        return Consumed::query()
            ->join('meal_items', 'consumeds.meal_item_id', '=', 'meal_items.id')
            ->where('consumeds.user_id', auth()->id())
            ->whereDate('consumeds.created_at', Carbon::today())
            ->selectRaw('
                COALESCE(SUM(consumeds.quantity * meal_items.carbs), 0) AS carbs,
                COALESCE(SUM(consumeds.quantity * meal_items.protein), 0) AS protein,
                COALESCE(SUM(consumeds.quantity * meal_items.fat), 0) AS fat,
                COALESCE(SUM(consumeds.quantity * meal_items.calories), 0) AS calories
            ')
            ->first();
    }

    #[Computed]
    public function achievements(): Collection
    {
        $personalBests = CompletedWorkout::where('user_id', auth()->id())
            ->where('is_deleted', false)
            ->selectRaw('MAX(weight) AS pb, equipment')
            ->groupBy('equipment')
            ->pluck('pb', 'equipment');

        return Achievement::orderBy('satisfied_by_item')
            ->orderBy('satisfied_by_amount')
            ->get()
            ->map(function ($achievement) use ($personalBests) {
                $pb = $personalBests[$achievement->satisfied_by_item] ?? null;
                $target = (float) $achievement->satisfied_by_amount;
                $unlocked = $pb !== null && $pb >= $target;
                $percent = ($pb !== null && $target > 0)
                    ? min(100, (int) round(($pb / $target) * 100))
                    : 0;

                return (object) [
                    'name' => $achievement->name,
                    'details' => $achievement->details,
                    'satisfied_by_amount' => $target,
                    'pb' => $pb,
                    'unlocked' => $unlocked,
                    'not_started' => $pb === null,
                    'percent' => $percent,
                ];
            });
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'meal_items_recorded' => Consumed::where('user_id', auth()->id())->count(),
            'workouts_recorded' => CompletedWorkout::where('user_id', auth()->id())->where('is_deleted', false)->count(),
        ];
    }
};
?>

<div class="af-stagger flex flex-col gap-6">
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    $today = $this->todaySchedule;
    $tomorrow = $this->tomorrowSchedule;
    $goals = $this->macroGoals;
    $used = $this->macrosUsedToday;
    $unlockedCount = $this->achievements->where('unlocked', true)->count();
    $macroMeta = [
        'protein' => ['label' => 'Protein', 'color' => 'text-sky-500', 'bar' => 'bg-sky-500', 'unit' => 'g'],
        'carbs' => ['label' => 'Carbs', 'color' => 'text-amber-500', 'bar' => 'bg-amber-500', 'unit' => 'g'],
        'fat' => ['label' => 'Fat', 'color' => 'text-rose-500', 'bar' => 'bg-rose-500', 'unit' => 'g'],
    ];
    $caloriePercent = $goals['calories'] ? ($used->calories / $goals['calories']) * 100 : 0;
    $caloriesLeft = $goals['calories'] ? round($goals['calories'] - $used->calories) : null;
@endphp

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-3xl bg-zinc-900 p-6 text-white shadow-xl shadow-emerald-900/10 sm:p-8 dark:bg-white/5 dark:ring-1 dark:ring-white/10" aria-labelledby="dashboard-greeting">
        <div class="af-hero absolute inset-0 opacity-90" aria-hidden="true"></div>
        <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
                <p class="text-sm font-medium text-emerald-300">{{ now()->format('l, j F') }}</p>
                <h1 id="dashboard-greeting" class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ $greeting }}, <span class="af-gradient-text">{{ $firstName }}</span>
                </h1>
                <p class="mt-3 max-w-xl text-zinc-300">
                    @if($today['is_rest_day'])
                        Rest day — recovery is part of the programme. Refuel, stretch and come back stronger.
                    @elseif($today['session'])
                        Today is <strong class="text-white">{{ $today['session']->primary_muscle_group }} &amp; {{ $today['session']->secondary_muscle_group }}</strong>.
                        @if($today['rotation'])
                            {{ $today['rotation']->program }} week: {{ $today['rotation']->sets }} × {{ $today['rotation']->reps }} at {{ $today['rotation']->weight_percent }}% of your 1RM.
                        @endif
                    @else
                        Training day — check your schedule for today's session.
                    @endif
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <flux:button :href="route('workouts')" wire:navigate variant="primary" icon="bolt" class="!rounded-full">
                        Log a workout
                    </flux:button>
                    <flux:button :href="route('nutrition')" wire:navigate icon="fire" class="!rounded-full !border-white/15 !bg-white/10 !text-white hover:!bg-white/20">
                        Log food
                    </flux:button>
                    <flux:button :href="route('weight')" wire:navigate icon="scale" class="!rounded-full !border-white/15 !bg-white/10 !text-white hover:!bg-white/20">
                        Weigh in
                    </flux:button>
                </div>
            </div>

            @if($goals['calories'])
                <div class="flex items-center gap-5 justify-self-start lg:justify-self-end">
                    <x-ui.ring :percent="$caloriePercent" :size="148" :stroke="12" :color="$caloriePercent > 100 ? 'text-rose-400' : 'text-emerald-400'" track="stroke-white/15" label="Calories eaten today">
                        <span class="text-3xl font-bold tabular-nums">{{ number_format(abs($caloriesLeft)) }}</span>
                        <span class="text-xs text-zinc-300">kcal {{ $caloriesLeft >= 0 ? 'left' : 'over' }}</span>
                    </x-ui.ring>
                    <ul class="space-y-3">
                        @foreach($macroMeta as $key => $meta)
                            @php $percent = $goals[$key] > 0 ? ($used->$key / $goals[$key]) * 100 : 0; @endphp
                            <li class="w-32">
                                <div class="flex items-baseline justify-between text-xs">
                                    <span class="text-zinc-300">{{ $meta['label'] }}</span>
                                    <span class="tabular-nums text-white">{{ round($used->$key) }}<span class="text-zinc-400">/{{ $goals[$key] }}g</span></span>
                                </div>
                                <x-ui.progress-bar :percent="$percent" :color="$meta['bar']" height="h-1.5" class="mt-1 !bg-white/15" :label="$meta['label'].' eaten today'" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>

    {{-- Stats --}}
    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Your stats">
        <x-ui.stat icon="bolt" tone="emerald" :value="number_format($this->stats['workouts_recorded'])" label="Workouts logged" :href="route('workouts')" wire:navigate />
        <x-ui.stat icon="fire" tone="amber" :value="number_format($this->stats['meal_items_recorded'])" label="Food entries logged" :href="route('nutrition')" wire:navigate />
        <x-ui.stat icon="trophy" tone="violet" :value="$unlockedCount.' / '.$this->achievements->count()" label="Achievements unlocked" wire:click="$set('showAchievementsModal', true)" />
        @if($goals['calories'])
            <x-ui.stat icon="chart-pie" tone="sky" :value="number_format($goals['calories'] - $used->calories)" label="Calories remaining today" :hint="$goals['calories'].' kcal daily target'" />
        @else
            <x-ui.stat icon="chart-pie" tone="sky" value="—" label="Set your profile goals" :href="route('profile.edit')" wire:navigate />
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-5">

        {{-- Today's workout --}}
        <flux:card class="!rounded-2xl lg:col-span-3">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Today · {{ $today['day'] }}</p>
                    <flux:heading size="lg" level="2" class="!text-xl font-bold">
                        @if($today['is_rest_day'])
                            Recovery day
                        @elseif($today['session'])
                            Session {{ strtoupper($today['session']->session) }} — {{ $today['session']->primary_muscle_group }} &amp; {{ $today['session']->secondary_muscle_group }}
                        @else
                            Training day
                        @endif
                    </flux:heading>
                </div>
                @if($today['rotation'])
                    <flux:badge color="emerald" icon="fire">{{ $today['rotation']->program }} week</flux:badge>
                @endif
            </div>

            @if($today['is_rest_day'])
                <x-ui.empty-state icon="moon" title="Rest day — recovery is part of the programme." description="Muscles grow while you rest. Hydrate, hit your protein and get a good night's sleep." />
            @else
                <div class="grid gap-6 md:grid-cols-2">
                    @foreach(['primary_exercises' => 'Primary', 'secondary_exercises' => 'Secondary'] as $listKey => $listLabel)
                        @if(count($today[$listKey]) > 0)
                            <div>
                                <p class="mb-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $listLabel }}</p>
                                <ol class="space-y-2">
                                    @foreach($today[$listKey] as $ex)
                                        <li class="flex items-center gap-3 rounded-xl bg-zinc-50 p-3 dark:bg-white/5">
                                            <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300" aria-hidden="true">{{ $loop->iteration }}</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium">{{ $ex->equipment }}</p>
                                                @if($today['rotation'])
                                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $today['rotation']->sets }} sets × {{ $today['rotation']->reps }} reps</p>
                                                @endif
                                            </div>
                                            @if($ex->weight_1rm)
                                                <flux:badge size="sm" color="zinc">{{ $ex->weight_1rm }}% 1RM</flux:badge>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="mt-5 flex justify-end">
                    <flux:button :href="route('workouts')" wire:navigate variant="primary" icon-trailing="arrow-right" size="sm">Start logging</flux:button>
                </div>
            @endif
        </flux:card>

        {{-- Tomorrow --}}
        <flux:card class="!rounded-2xl lg:col-span-2">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-zinc-600 dark:text-zinc-400">Up next · {{ $tomorrow['day'] }}</p>
                    <flux:heading size="lg" level="2" class="!text-xl font-bold">
                        @if($tomorrow['is_rest_day'])
                            Rest day tomorrow.
                        @elseif($tomorrow['session'])
                            {{ $tomorrow['session']->primary_muscle_group }} &amp; {{ $tomorrow['session']->secondary_muscle_group }}
                        @else
                            Training day
                        @endif
                    </flux:heading>
                </div>
                @if($tomorrow['rotation'])
                    <flux:badge color="zinc">{{ $tomorrow['rotation']->program }}</flux:badge>
                @endif
            </div>

            @if($tomorrow['is_rest_day'])
                <x-ui.empty-state icon="moon" title="Time to recharge" description="Enjoy the break — you've earned it." />
            @else
                <ul class="flex flex-wrap gap-2">
                    @foreach(collect($tomorrow['primary_exercises'])->concat($tomorrow['secondary_exercises']) as $ex)
                        <li class="rounded-full border border-zinc-200 px-3 py-1 text-sm dark:border-white/10">{{ $ex->equipment }}</li>
                    @endforeach
                </ul>
                <div class="mt-5">
                    <flux:link :href="route('schedule')" wire:navigate class="text-sm">See full schedule →</flux:link>
                </div>
            @endif
        </flux:card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Personal Bests --}}
        <flux:card class="!rounded-2xl">
            <div class="mb-4 flex items-center gap-2">
                <flux:icon.trophy class="size-5 text-amber-500" aria-hidden="true" />
                <flux:heading size="lg" level="2">Personal Bests (Big 4)</flux:heading>
            </div>
            <ul class="grid grid-cols-2 gap-3">
                @foreach($this->personalBests as $pb)
                    <li class="rounded-xl border p-4 {{ $pb['lbs'] ? 'border-amber-200 bg-linear-to-br from-amber-50 to-white dark:border-amber-400/20 dark:from-amber-400/10 dark:to-transparent' : 'border-zinc-200 dark:border-white/10' }}">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $pb['name'] }}</p>
                        @if($pb['lbs'])
                            <p class="mt-1 text-2xl font-bold tabular-nums">{{ $pb['lbs'] }} <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">lbs</span></p>
                        @else
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No data yet</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </flux:card>

        {{-- Daily Macros --}}
        <flux:card class="!rounded-2xl">
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <flux:icon.chart-pie class="size-5 text-emerald-500" aria-hidden="true" />
                    <flux:heading size="lg" level="2">Today's Macros</flux:heading>
                </div>
                <flux:link :href="route('nutrition')" wire:navigate class="text-sm">Log food →</flux:link>
            </div>
            @if($goals['calories'])
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach(['protein' => 'Protein', 'carbs' => 'Carbs', 'fat' => 'Fat', 'calories' => 'Calories'] as $key => $label)
                        @php
                            $percent = $goals[$key] > 0 ? ($used->$key / $goals[$key]) * 100 : 0;
                            $colour = $key === 'calories' ? 'text-emerald-500' : $macroMeta[$key]['color'];
                        @endphp
                        <div class="flex flex-col items-center text-center">
                            <x-ui.ring :percent="$percent" :size="84" :stroke="8" :color="$percent > 100 ? 'text-rose-500' : $colour" :label="$label.' eaten today'">
                                <span class="text-sm font-bold tabular-nums">{{ round($percent) }}%</span>
                            </x-ui.ring>
                            <p class="mt-2 text-sm font-medium">{{ $label }}</p>
                            <p class="text-xs tabular-nums text-zinc-600 dark:text-zinc-400">{{ round($used->$key) }} / {{ $goals[$key] }}{{ $key === 'calories' ? 'kcal' : 'g' }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:callout icon="information-circle" color="sky">
                    <flux:callout.text>
                        Set your body weight and fitness goal in <flux:link href="{{ route('profile.edit') }}" wire:navigate>Profile settings</flux:link> to see macro targets.
                    </flux:callout.text>
                </flux:callout>
            @endif
        </flux:card>
    </div>

    {{-- Achievements --}}
    @if($this->achievements->count())
        <flux:card class="!rounded-2xl">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <flux:icon.sparkles class="size-5 text-violet-500" aria-hidden="true" />
                    <flux:heading size="lg" level="2">Achievements</flux:heading>
                    <flux:badge size="sm" color="violet">{{ $unlockedCount }} / {{ $this->achievements->count() }}</flux:badge>
                </div>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" wire:click="$set('showAchievementsModal', true)">View progress</flux:button>
            </div>
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($this->achievements as $achievement)
                    <li class="relative flex items-start gap-3 overflow-hidden rounded-xl border p-3 {{ $achievement->unlocked ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10' : 'border-zinc-200 dark:border-white/10' }}">
                        @if($achievement->unlocked)
                            <div class="af-shine pointer-events-none absolute inset-0" aria-hidden="true"></div>
                        @endif
                        <div class="relative flex size-10 shrink-0 items-center justify-center rounded-full {{ $achievement->unlocked ? 'bg-linear-to-br from-emerald-400 to-cyan-500 text-white shadow-md shadow-emerald-500/30' : 'bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-400' }}" aria-hidden="true">
                            <flux:icon :icon="$achievement->unlocked ? 'trophy' : 'lock-closed'" variant="solid" class="size-5" />
                        </div>
                        <div class="relative min-w-0 flex-1">
                            <p class="font-medium {{ $achievement->unlocked ? 'text-emerald-800 dark:text-emerald-300' : '' }}">{{ $achievement->name }}</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $achievement->details }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">PB: {{ $achievement->pb ?? '–' }} / {{ $achievement->satisfied_by_amount }} lbs</p>
                            @unless($achievement->unlocked)
                                <x-ui.progress-bar :percent="$achievement->percent" color="bg-violet-500" height="h-1.5" class="mt-2" :label="$achievement->name.' progress'" />
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        </flux:card>
    @endif

    {{-- Achievements Modal --}}
    <flux:modal wire:model="showAchievementsModal" class="w-full max-w-3xl">
        <flux:heading size="lg" class="mb-1">Achievements</flux:heading>
        <flux:subheading class="mb-6">
            {{ $unlockedCount }} / {{ $this->achievements->count() }} unlocked
        </flux:subheading>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($this->achievements as $achievement)
                @php
                    $barColor = match(true) {
                        $achievement->percent >= 75 => 'bg-emerald-500',
                        $achievement->percent >= 50 => 'bg-amber-500',
                        default                     => 'bg-rose-500',
                    };
                @endphp

                <div class="rounded-xl border p-4 {{ $achievement->unlocked ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10' : 'border-zinc-200 dark:border-white/10' }}">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $achievement->unlocked ? 'bg-linear-to-br from-emerald-400 to-cyan-500 text-white' : 'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300' }}" aria-hidden="true">
                            <flux:icon :icon="$achievement->unlocked ? 'trophy' : 'bolt'" variant="solid" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ $achievement->name }}</p>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $achievement->details }}</p>
                        </div>
                        @if($achievement->unlocked)
                            <flux:badge color="emerald" size="sm">Achieved</flux:badge>
                        @endif
                    </div>

                    <div class="mt-3">
                        @if($achievement->unlocked)
                            <p class="text-sm text-emerald-800 dark:text-emerald-300">{{ $achievement->pb }} / {{ $achievement->satisfied_by_amount }} lbs — Completed!</p>
                        @elseif($achievement->not_started)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Not started</p>
                            <x-ui.progress-bar :percent="0" class="mt-1.5" :label="$achievement->name.' progress'" />
                        @else
                            <div class="flex items-baseline justify-between">
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $achievement->pb }} / {{ $achievement->satisfied_by_amount }} lbs</p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $achievement->percent }}%</p>
                            </div>
                            <x-ui.progress-bar :percent="$achievement->percent" :color="$barColor" class="mt-1.5" :label="$achievement->name.' progress'" />
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <flux:modal.close>
                <flux:button>Close</flux:button>
            </flux:modal.close>
        </div>
    </flux:modal>

</div>
