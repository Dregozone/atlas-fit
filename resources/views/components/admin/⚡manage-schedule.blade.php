<?php

use App\Models\Day;
use App\Models\Rotation;
use App\Models\WorkoutSession;
use App\Models\Workout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Manage Schedule')] class extends Component {

    // Active tab
    public string $activeTab = 'rotations';

    // Rotation editing
    public ?int $editingRotationId = null;

    #[Validate('required|integer|min:1|max:3')]
    public int $rotationWeek = 1;

    #[Validate('required|string|max:100')]
    public string $rotationProgram = '';

    #[Validate('required|string|max:50')]
    public string $rotationSets = '';

    #[Validate('required|string|max:50')]
    public string $rotationReps = '';

    #[Validate('required|integer|min:1|max:100')]
    public int $rotationWeightPercent = 100;

    // Workout session editing
    public ?int $editingSessionId = null;

    #[Validate('required|string|max:50')]
    public string $sessionKey = '';

    #[Validate('required|string|max:100')]
    public string $sessionPrimary = '';

    #[Validate('required|string|max:100')]
    public string $sessionSecondary = '';

    // Day editing
    public ?int $editingDayId = null;

    #[Validate('required|string|max:20')]
    public string $dayName = '';

    #[Validate('nullable|string|max:50')]
    public string $daySession = '';

    // Workout exercise editing
    public ?int $editingWorkoutId = null;

    #[Validate('required|string|max:100')]
    public string $workoutSession = '';

    #[Validate('required|string|max:255')]
    public string $workoutEquipment = '';

    #[Validate('required|integer|min:1')]
    public int $workoutExerciseNo = 1;

    #[Validate('nullable|integer|min:1|max:100')]
    public ?int $workoutWeight1rm = null;

    public string $successMessage = '';

    #[Computed]
    public function rotations()
    {
        return Rotation::orderBy('week')->get();
    }

    #[Computed]
    public function sessions()
    {
        return WorkoutSession::orderBy('session')->get();
    }

    #[Computed]
    public function days()
    {
        $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return Day::all()->sortBy(fn ($d) => array_search($d->day, $order));
    }

    #[Computed]
    public function workouts()
    {
        return Workout::orderBy('session')->orderBy('exercise_no')->get();
    }

    // ── Rotation CRUD ─────────────────────────────────────────────────────────

    public function editRotation(int $id): void
    {
        $rotation = Rotation::findOrFail($id);
        $this->editingRotationId = $id;
        $this->rotationWeek = $rotation->week;
        $this->rotationProgram = $rotation->program;
        $this->rotationSets = $rotation->sets;
        $this->rotationReps = $rotation->reps;
        $this->rotationWeightPercent = $rotation->weight_percent;
    }

    public function saveRotation(): void
    {
        $this->validateOnly('rotationWeek');
        $this->validateOnly('rotationProgram');
        $this->validateOnly('rotationSets');
        $this->validateOnly('rotationReps');
        $this->validateOnly('rotationWeightPercent');

        Rotation::findOrFail($this->editingRotationId)->update([
            'week' => $this->rotationWeek,
            'program' => $this->rotationProgram,
            'sets' => $this->rotationSets,
            'reps' => $this->rotationReps,
            'weight_percent' => $this->rotationWeightPercent,
        ]);

        $this->reset(['editingRotationId', 'rotationWeek', 'rotationProgram', 'rotationSets', 'rotationReps', 'rotationWeightPercent']);
        $this->successMessage = 'Rotation updated.';
        unset($this->rotations);
    }

    public function cancelRotationEdit(): void
    {
        $this->reset(['editingRotationId', 'rotationWeek', 'rotationProgram', 'rotationSets', 'rotationReps', 'rotationWeightPercent']);
    }

    // ── Day CRUD ──────────────────────────────────────────────────────────────

    public function editDay(int $id): void
    {
        $day = Day::findOrFail($id);
        $this->editingDayId = $id;
        $this->dayName = $day->day;
        $this->daySession = $day->session ?? '';
    }

    public function saveDay(): void
    {
        $this->validateOnly('dayName');

        Day::findOrFail($this->editingDayId)->update([
            'day' => $this->dayName,
            'session' => $this->daySession ?: null,
        ]);

        $this->reset(['editingDayId', 'dayName', 'daySession']);
        $this->successMessage = 'Day updated.';
        unset($this->days);
    }

    public function cancelDayEdit(): void
    {
        $this->reset(['editingDayId', 'dayName', 'daySession']);
    }

    // ── Workout Exercise CRUD ─────────────────────────────────────────────────

    public function editWorkout(int $id): void
    {
        $workout = Workout::findOrFail($id);
        $this->editingWorkoutId = $id;
        $this->workoutSession = $workout->session;
        $this->workoutEquipment = $workout->equipment;
        $this->workoutExerciseNo = $workout->exercise_no;
        $this->workoutWeight1rm = $workout->weight_1rm;
    }

    public function saveWorkout(): void
    {
        $this->validateOnly('workoutSession');
        $this->validateOnly('workoutEquipment');
        $this->validateOnly('workoutExerciseNo');
        $this->validateOnly('workoutWeight1rm');

        Workout::findOrFail($this->editingWorkoutId)->update([
            'session' => $this->workoutSession,
            'equipment' => $this->workoutEquipment,
            'exercise_no' => $this->workoutExerciseNo,
            'weight_1rm' => $this->workoutWeight1rm,
        ]);

        $this->reset(['editingWorkoutId', 'workoutSession', 'workoutEquipment', 'workoutExerciseNo', 'workoutWeight1rm']);
        $this->successMessage = 'Exercise updated.';
        unset($this->workouts);
    }

    public function cancelWorkoutEdit(): void
    {
        $this->reset(['editingWorkoutId', 'workoutSession', 'workoutEquipment', 'workoutExerciseNo', 'workoutWeight1rm']);
    }
};
?>

    <div class="flex flex-col gap-6 p-6">

        <div>
            <flux:heading size="xl">Manage Schedule</flux:heading>
            <flux:text class="text-zinc-500">Edit the workout programme, sessions, day assignments, and exercises.</flux:text>
        </div>

        @if($successMessage)
            <flux:callout icon="check-circle" color="green">
                <flux:callout.text>{{ $successMessage }}</flux:callout.text>
            </flux:callout>
        @endif

        <flux:tabs wire:model="activeTab">
            <flux:tab name="rotations">Rotations</flux:tab>
            <flux:tab name="days">Days</flux:tab>
            <flux:tab name="exercises">Exercises</flux:tab>

            <flux:tab.panel name="rotations">
                <flux:card class="mt-4">
                    <flux:heading size="lg" class="mb-4">3-Week Rotation Programme</flux:heading>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Week</flux:table.column>
                            <flux:table.column>Programme</flux:table.column>
                            <flux:table.column>Sets</flux:table.column>
                            <flux:table.column>Reps</flux:table.column>
                            <flux:table.column>Weight %</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->rotations as $rotation)
                                <flux:table.row>
                                    @if($editingRotationId === $rotation->id)
                                        <flux:table.cell><flux:input wire:model="rotationWeek" type="number" min="1" max="3" class="w-16" /></flux:table.cell>
                                        <flux:table.cell><flux:input wire:model="rotationProgram" /></flux:table.cell>
                                        <flux:table.cell><flux:input wire:model="rotationSets" /></flux:table.cell>
                                        <flux:table.cell><flux:input wire:model="rotationReps" /></flux:table.cell>
                                        <flux:table.cell><flux:input wire:model="rotationWeightPercent" type="number" class="w-20" /></flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex gap-2">
                                                <flux:button wire:click="saveRotation" size="sm" variant="primary">Save</flux:button>
                                                <flux:button wire:click="cancelRotationEdit" size="sm" variant="ghost">Cancel</flux:button>
                                            </div>
                                        </flux:table.cell>
                                    @else
                                        <flux:table.cell>{{ $rotation->week }}</flux:table.cell>
                                        <flux:table.cell>{{ $rotation->program }}</flux:table.cell>
                                        <flux:table.cell>{{ $rotation->sets }}</flux:table.cell>
                                        <flux:table.cell>{{ $rotation->reps }}</flux:table.cell>
                                        <flux:table.cell>{{ $rotation->weight_percent }}%</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button wire:click="editRotation({{ $rotation->id }})" size="sm" icon="pencil" variant="ghost" />
                                        </flux:table.cell>
                                    @endif
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            </flux:tab.panel>

            <flux:tab.panel name="days">
                <flux:card class="mt-4">
                    <flux:heading size="lg" class="mb-4">Day → Session Assignments</flux:heading>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Day</flux:table.column>
                            <flux:table.column>Session</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->days as $day)
                                <flux:table.row>
                                    @if($editingDayId === $day->id)
                                        <flux:table.cell>
                                            <flux:input wire:model="dayName" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:select wire:model="daySession">
                                                <flux:select.option value="">— Rest day —</flux:select.option>
                                                @foreach($this->sessions as $session)
                                                    <flux:select.option :value="$session->session">{{ $session->session }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex gap-2">
                                                <flux:button wire:click="saveDay" size="sm" variant="primary">Save</flux:button>
                                                <flux:button wire:click="cancelDayEdit" size="sm" variant="ghost">Cancel</flux:button>
                                            </div>
                                        </flux:table.cell>
                                    @else
                                        <flux:table.cell class="font-medium">{{ $day->day }}</flux:table.cell>
                                        <flux:table.cell>
                                            @if($day->session)
                                                <flux:badge color="blue">{{ $day->session }}</flux:badge>
                                            @else
                                                <flux:badge color="zinc">Rest</flux:badge>
                                            @endif
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button wire:click="editDay({{ $day->id }})" size="sm" icon="pencil" variant="ghost" />
                                        </flux:table.cell>
                                    @endif
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            </flux:tab.panel>

            <flux:tab.panel name="exercises">
                <flux:card class="mt-4">
                    <flux:heading size="lg" class="mb-4">Exercises</flux:heading>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Session</flux:table.column>
                            <flux:table.column>#</flux:table.column>
                            <flux:table.column>Exercise</flux:table.column>
                            <flux:table.column>1RM %</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->workouts as $workout)
                                <flux:table.row>
                                    @if($editingWorkoutId === $workout->id)
                                        <flux:table.cell><flux:input wire:model="workoutSession" /></flux:table.cell>
                                        <flux:table.cell><flux:input wire:model="workoutExerciseNo" type="number" class="w-16" /></flux:table.cell>
                                        <flux:table.cell><flux:input wire:model="workoutEquipment" /></flux:table.cell>
                                        <flux:table.cell><flux:input wire:model="workoutWeight1rm" type="number" class="w-20" /></flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex gap-2">
                                                <flux:button wire:click="saveWorkout" size="sm" variant="primary">Save</flux:button>
                                                <flux:button wire:click="cancelWorkoutEdit" size="sm" variant="ghost">Cancel</flux:button>
                                            </div>
                                        </flux:table.cell>
                                    @else
                                        <flux:table.cell>{{ $workout->session }}</flux:table.cell>
                                        <flux:table.cell>{{ $workout->exercise_no }}</flux:table.cell>
                                        <flux:table.cell class="font-medium">{{ $workout->equipment }}</flux:table.cell>
                                        <flux:table.cell>{{ $workout->weight_1rm ? $workout->weight_1rm . '%' : '—' }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button wire:click="editWorkout({{ $workout->id }})" size="sm" icon="pencil" variant="ghost" />
                                        </flux:table.cell>
                                    @endif
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            </flux:tab.panel>
        </flux:tabs>

    </div>

</div>