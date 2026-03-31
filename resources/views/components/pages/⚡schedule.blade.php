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

    <div class="flex flex-col gap-6 p-6">

        <div>
            <flux:heading size="xl">Programme Schedule</flux:heading>
            <flux:text class="text-zinc-500">3-week rotating programme. Each week has a different intensity.</flux:text>
        </div>

        {{-- 3-Week Rotation Overview --}}
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach($this->rotations as $rotation)
                <flux:card>
                    <flux:heading size="lg" class="mb-1">Week {{ $rotation->week }}</flux:heading>
                    <flux:badge color="blue" class="mb-3">{{ $rotation->program }}</flux:badge>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-500">Sets</flux:text>
                            <flux:text class="font-medium">{{ $rotation->sets }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-500">Reps</flux:text>
                            <flux:text class="font-medium">{{ $rotation->reps }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-500">Weight</flux:text>
                            <flux:text class="font-medium">{{ $rotation->weight_percent }}% 1RM</flux:text>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>

        {{-- Weekly Day Breakdown --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Weekly Day Plan</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Day</flux:table.column>
                    <flux:table.column>Session</flux:table.column>
                    <flux:table.column>Primary Focus</flux:table.column>
                    <flux:table.column>Secondary Focus</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $dayName)
                        @php
                            $day = $this->days->get($dayName);
                            $sessionKey = $day?->session ?? '';
                            $session = $sessionKey ? $this->sessions->get($sessionKey) : null;
                        @endphp
                        <flux:table.row>
                            <flux:table.cell class="font-medium">{{ $dayName }}</flux:table.cell>
                            <flux:table.cell>
                                @if($sessionKey)
                                    <flux:badge color="blue">{{ $sessionKey }}</flux:badge>
                                @else
                                    <flux:badge color="zinc">Rest</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $session?->primary_muscle_group ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $session?->secondary_muscle_group ?? '—' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Session Details --}}
        @foreach($this->sessions as $sessionKey => $session)
            <flux:card>
                <flux:heading size="lg" class="mb-1">Session {{ $sessionKey }}</flux:heading>
                <div class="mb-4 flex gap-2">
                    <flux:badge color="blue">Primary: {{ $session->primary_muscle_group }}</flux:badge>
                    <flux:badge color="zinc">Secondary: {{ $session->secondary_muscle_group }}</flux:badge>
                </div>

                @php
                    $primaryExercises = $this->workoutsBySession[$session->primary_muscle_group] ?? [];
                    $secondaryExercises = $this->workoutsBySession[$session->secondary_muscle_group] ?? [];
                @endphp

                <div class="grid gap-4 md:grid-cols-2">
                    @if(count($primaryExercises))
                        <div>
                            <flux:text class="mb-2 font-semibold text-zinc-700 dark:text-zinc-300">Primary Exercises</flux:text>
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>#</flux:table.column>
                                    <flux:table.column>Exercise</flux:table.column>
                                    <flux:table.column>1RM %</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach($primaryExercises as $ex)
                                        <flux:table.row>
                                            <flux:table.cell>{{ $ex->exercise_no }}</flux:table.cell>
                                            <flux:table.cell>{{ $ex->equipment }}</flux:table.cell>
                                            <flux:table.cell>{{ $ex->weight_1rm ? $ex->weight_1rm . '%' : '—' }}</flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    @endif

                    @if(count($secondaryExercises))
                        <div>
                            <flux:text class="mb-2 font-semibold text-zinc-700 dark:text-zinc-300">Secondary Exercises</flux:text>
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>#</flux:table.column>
                                    <flux:table.column>Exercise</flux:table.column>
                                    <flux:table.column>1RM %</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach($secondaryExercises as $ex)
                                        <flux:table.row>
                                            <flux:table.cell>{{ $ex->exercise_no }}</flux:table.cell>
                                            <flux:table.cell>{{ $ex->equipment }}</flux:table.cell>
                                            <flux:table.cell>{{ $ex->weight_1rm ? $ex->weight_1rm . '%' : '—' }}</flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endforeach

    </div>
