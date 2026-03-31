<?php

use App\Models\Workout;
use App\Models\CompletedWorkout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Workouts')] class extends Component {

    #[Validate('required|string|max:255')]
    public string $equipment = '';

    #[Validate('required|integer|min:1|max:100')]
    public int $sets = 3;

    #[Validate('required|integer|min:1|max:100')]
    public int $reps = 5;

    #[Validate('required|numeric|min:0|max:2000')]
    public float $weight = 0;

    public bool $showSuccess = false;

    #[Computed]
    public function availableExercises()
    {
        return Workout::orderBy('equipment')->pluck('equipment')->unique()->values();
    }

    #[Computed]
    public function recentWorkouts()
    {
        return CompletedWorkout::where('user_id', auth()->id())
            ->where('is_deleted', false)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
    }

    public function logWorkout(): void
    {
        $this->validate();

        CompletedWorkout::create([
            'user_id' => auth()->id(),
            'equipment' => $this->equipment,
            'sets' => $this->sets,
            'reps' => $this->reps,
            'weight' => $this->weight,
            'is_deleted' => false,
        ]);

        $this->reset(['equipment', 'sets', 'reps', 'weight']);
        $this->sets = 3;
        $this->reps = 5;
        $this->showSuccess = true;
        unset($this->recentWorkouts);
    }

    public function deleteWorkout(int $id): void
    {
        $workout = CompletedWorkout::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $workout->update(['is_deleted' => true]);
        unset($this->recentWorkouts);
    }
};
?>

    <div class="flex flex-col gap-6 p-6">

        <div>
            <flux:heading size="xl">Workouts</flux:heading>
            <flux:text class="text-zinc-500">Log your workout sets and track your progress.</flux:text>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Log Workout Form --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Log a Workout</flux:heading>

                @if($showSuccess)
                    <flux:callout icon="check-circle" color="green" class="mb-4">
                        <flux:callout.text>Workout logged successfully!</flux:callout.text>
                    </flux:callout>
                @endif

                <form wire:submit="logWorkout" class="space-y-4">
                    <flux:field>
                        <flux:label>Exercise</flux:label>
                        <flux:select wire:model="equipment" placeholder="Select an exercise...">
                            @foreach($this->availableExercises as $ex)
                                <flux:select.option :value="$ex">{{ $ex }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="equipment" />
                    </flux:field>

                    <div class="grid grid-cols-3 gap-4">
                        <flux:field>
                            <flux:label>Sets</flux:label>
                            <flux:input wire:model="sets" type="number" min="1" max="100" />
                            <flux:error name="sets" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Reps</flux:label>
                            <flux:input wire:model="reps" type="number" min="1" max="100" />
                            <flux:error name="reps" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Weight (lbs)</flux:label>
                            <flux:input wire:model="weight" type="number" min="0" step="2.5" />
                            <flux:error name="weight" />
                        </flux:field>
                    </div>

                    <flux:button type="submit" variant="primary" class="w-full">Log Workout</flux:button>
                </form>
            </flux:card>

            {{-- Recent Workouts --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Recent Workouts (Last 10)</flux:heading>

                @if($this->recentWorkouts->isEmpty())
                    <flux:text class="text-zinc-500">No workouts logged yet. Get after it!</flux:text>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Exercise</flux:table.column>
                            <flux:table.column>Sets</flux:table.column>
                            <flux:table.column>Reps</flux:table.column>
                            <flux:table.column>Weight</flux:table.column>
                            <flux:table.column>Date</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->recentWorkouts as $workout)
                                <flux:table.row>
                                    <flux:table.cell class="font-medium">{{ $workout->equipment }}</flux:table.cell>
                                    <flux:table.cell>{{ $workout->sets }}</flux:table.cell>
                                    <flux:table.cell>{{ $workout->reps }}</flux:table.cell>
                                    <flux:table.cell>{{ $workout->weight }} lbs</flux:table.cell>
                                    <flux:table.cell class="text-zinc-400 text-sm">{{ $workout->created_at->format('d M') }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button
                                            wire:click="deleteWorkout({{ $workout->id }})"
                                            wire:confirm="Remove this workout entry?"
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                        />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </flux:card>

        </div>
    </div>

</div>