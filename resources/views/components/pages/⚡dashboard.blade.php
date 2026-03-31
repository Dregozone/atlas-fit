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
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {

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
        $pbOrder = ['Overhead press', 'Bench press', 'Squat', 'Deadlift'];
        $pbs = CompletedWorkout::where('user_id', auth()->id())
            ->where('is_deleted', false)
            ->whereIn('equipment', $pbOrder)
            ->selectRaw('MAX(weight) AS lbs, equipment')
            ->groupBy('equipment')
            ->pluck('lbs', 'equipment')
            ->toArray();

        return array_map(fn ($name) => [
            'name' => $name,
            'lbs' => $pbs[$name] ?? null,
        ], $pbOrder);
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
    public function achievements()
    {
        return Achievement::query()
            ->join('completed_workouts', 'achievements.satisfied_by_item', '=', 'completed_workouts.equipment')
            ->where('completed_workouts.user_id', auth()->id())
            ->where('completed_workouts.is_deleted', false)
            ->selectRaw('
                achievements.name,
                achievements.details,
                achievements.satisfied_by_amount,
                MAX(completed_workouts.weight) AS pb
            ')
            ->groupBy('achievements.name', 'achievements.details', 'achievements.satisfied_by_amount')
            ->get();
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

    <div class="flex flex-col gap-6 p-6">

        {{-- Stats row --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <flux:card class="text-center">
                <flux:heading size="xl">{{ $this->stats['workouts_recorded'] }}</flux:heading>
                <flux:text>Workouts logged</flux:text>
            </flux:card>
            <flux:card class="text-center">
                <flux:heading size="xl">{{ $this->stats['meal_items_recorded'] }}</flux:heading>
                <flux:text>Food entries logged</flux:text>
            </flux:card>
            <flux:card class="text-center">
                <flux:heading size="xl">{{ $this->achievements->where('pb', '>=', 'satisfied_by_amount')->count() }} / {{ $this->achievements->count() }}</flux:heading>
                <flux:text>Achievements unlocked</flux:text>
            </flux:card>
            <flux:card class="text-center">
                @if($this->macroGoals['calories'])
                    <flux:heading size="xl">{{ number_format($this->macroGoals['calories'] - $this->macrosUsedToday->calories) }}</flux:heading>
                    <flux:text>Calories remaining today</flux:text>
                @else
                    <flux:heading size="xl">—</flux:heading>
                    <flux:text>Set your profile goals</flux:text>
                @endif
            </flux:card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Today's Workout --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">
                    Today — {{ $this->todaySchedule['day'] }}
                    @if($this->todaySchedule['rotation'])
                        <flux:badge color="blue" size="sm" class="ml-2">{{ $this->todaySchedule['rotation']->program }}</flux:badge>
                    @endif
                </flux:heading>

                @if($this->todaySchedule['is_rest_day'])
                    <flux:text class="text-zinc-500">Rest day — recovery is part of the programme.</flux:text>
                @else
                    @if($this->todaySchedule['session'])
                        <flux:text class="mb-3 font-medium">Session: {{ $this->todaySchedule['session']->session }}</flux:text>
                    @endif

                    @if(count($this->todaySchedule['primary_exercises']) > 0)
                        <flux:text class="mb-1 font-semibold text-zinc-700 dark:text-zinc-300">Primary</flux:text>
                        <flux:table class="mb-4">
                            <flux:table.columns>
                                <flux:table.column>Exercise</flux:table.column>
                                <flux:table.column>Sets × Reps</flux:table.column>
                                <flux:table.column>1RM %</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($this->todaySchedule['primary_exercises'] as $ex)
                                    <flux:table.row>
                                        <flux:table.cell>{{ $ex->equipment }}</flux:table.cell>
                                        <flux:table.cell>—</flux:table.cell>
                                        <flux:table.cell>{{ $ex->weight_1rm ? $ex->weight_1rm . '%' : '—' }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif

                    @if(count($this->todaySchedule['secondary_exercises']) > 0)
                        <flux:text class="mb-1 font-semibold text-zinc-700 dark:text-zinc-300">Secondary</flux:text>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Exercise</flux:table.column>
                                <flux:table.column>1RM %</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($this->todaySchedule['secondary_exercises'] as $ex)
                                    <flux:table.row>
                                        <flux:table.cell>{{ $ex->equipment }}</flux:table.cell>
                                        <flux:table.cell>{{ $ex->weight_1rm ? $ex->weight_1rm . '%' : '—' }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif
                @endif
            </flux:card>

            {{-- Tomorrow's Workout --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">
                    Tomorrow — {{ $this->tomorrowSchedule['day'] }}
                    @if($this->tomorrowSchedule['rotation'])
                        <flux:badge color="zinc" size="sm" class="ml-2">{{ $this->tomorrowSchedule['rotation']->program }}</flux:badge>
                    @endif
                </flux:heading>

                @if($this->tomorrowSchedule['is_rest_day'])
                    <flux:text class="text-zinc-500">Rest day tomorrow.</flux:text>
                @else
                    @if($this->tomorrowSchedule['session'])
                        <flux:text class="mb-3 font-medium">Session: {{ $this->tomorrowSchedule['session']->session }}</flux:text>
                    @endif

                    @if(count($this->tomorrowSchedule['primary_exercises']) > 0)
                        <flux:text class="mb-1 font-semibold text-zinc-700 dark:text-zinc-300">Primary</flux:text>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Exercise</flux:table.column>
                                <flux:table.column>1RM %</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($this->tomorrowSchedule['primary_exercises'] as $ex)
                                    <flux:table.row>
                                        <flux:table.cell>{{ $ex->equipment }}</flux:table.cell>
                                        <flux:table.cell>{{ $ex->weight_1rm ? $ex->weight_1rm . '%' : '—' }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif
                @endif
            </flux:card>

        </div>

        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Personal Bests --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Personal Bests (Big 4)</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Lift</flux:table.column>
                        <flux:table.column>Best (lbs)</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->personalBests as $pb)
                            <flux:table.row>
                                <flux:table.cell>{{ $pb['name'] }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($pb['lbs'])
                                        <flux:badge color="green">{{ $pb['lbs'] }} lbs</flux:badge>
                                    @else
                                        <flux:text class="text-zinc-400">No data yet</flux:text>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            {{-- Daily Macros --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Today's Macros</flux:heading>
                @if($this->macroGoals['calories'])
                    <div class="grid grid-cols-2 gap-4">
                        @foreach(['protein' => 'Protein', 'carbs' => 'Carbs', 'fat' => 'Fat', 'calories' => 'Calories'] as $key => $label)
                            <div>
                                <flux:text class="text-sm font-medium text-zinc-500">{{ $label }}</flux:text>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xl font-bold">{{ round($this->macrosUsedToday->$key) }}</span>
                                    <span class="text-sm text-zinc-400">/ {{ $this->macroGoals[$key] }}{{ $key === 'calories' ? 'kcal' : 'g' }}</span>
                                </div>
                                @php $percent = $this->macroGoals[$key] > 0 ? min(100, round(($this->macrosUsedToday->$key / $this->macroGoals[$key]) * 100)) : 0; @endphp
                                <div class="mt-1 h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <div class="h-2 rounded-full bg-blue-500 transition-all" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <flux:callout icon="information-circle">
                        <flux:callout.text>
                            Set your body weight and fitness goal in <flux:link href="{{ route('profile.edit') }}" wire:navigate>Profile settings</flux:link> to see macro targets.
                        </flux:callout.text>
                    </flux:callout>
                @endif
            </flux:card>

        </div>

        {{-- Achievements --}}
        @if($this->achievements->count())
        <flux:card>
            <flux:heading size="lg" class="mb-4">Achievements</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($this->achievements as $achievement)
                    @php $unlocked = $achievement->pb >= $achievement->satisfied_by_amount; @endphp
                    <div class="flex items-start gap-3 rounded-lg border p-3 {{ $unlocked ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950' : 'border-zinc-200 dark:border-zinc-700' }}">
                        <flux:icon.bolt class="mt-0.5 size-5 shrink-0 {{ $unlocked ? 'text-green-500' : 'text-zinc-400' }}" />
                        <div>
                            <p class="font-medium {{ $unlocked ? 'text-green-700 dark:text-green-300' : '' }}">{{ $achievement->name }}</p>
                            <p class="text-sm text-zinc-500">{{ $achievement->details }}</p>
                            <p class="text-xs text-zinc-400">PB: {{ $achievement->pb ?? '–' }} / {{ $achievement->satisfied_by_amount }} lbs</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
        @endif

    </div>
