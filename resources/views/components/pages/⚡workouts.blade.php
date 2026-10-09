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

        $this->dispatch('celebrate', message: 'Workout logged — great work!');
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

<div class="af-stagger flex flex-col gap-6">

    <x-ui.page-header icon="bolt" eyebrow="Train" title="Workouts" subtitle="Log your workout sets and track your progress." />

    <div class="grid gap-6 lg:grid-cols-5">

        {{-- Log Workout Form --}}
        <flux:card class="!rounded-2xl lg:col-span-2">
            <flux:heading size="lg" level="2" class="mb-1">Log a Workout</flux:heading>
            <flux:text class="mb-5">Every set counts. Pick an exercise and record what you lifted.</flux:text>

            @if($showSuccess)
                <x-ui.success-banner title="Workout logged successfully!" class="mb-5" wire:key="workout-success-{{ $this->recentWorkouts->first()?->id }}">
                    Another one in the books — keep that momentum going.
                </x-ui.success-banner>
            @endif

            <form wire:submit="logWorkout" class="space-y-5"
                x-data="{
                    step(field, amount, min, max) {
                        const next = Math.round((Number($wire[field]) + amount) * 10) / 10;
                        $wire[field] = Math.min(max, Math.max(min, next));
                    },
                }"
            >
                <flux:field>
                    <flux:label>Exercise</flux:label>
                    <flux:select variant="listbox" searchable wire:model="equipment" placeholder="Select an exercise...">
                        @foreach($this->availableExercises as $ex)
                            <flux:select.option :value="$ex">{{ $ex }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="equipment" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    @foreach(['sets' => ['Sets', 1, 1, 100], 'reps' => ['Reps', 1, 1, 100]] as $field => [$label, $stepBy, $min, $max])
                        <flux:field>
                            <flux:label>{{ $label }}</flux:label>
                            <div class="flex items-center gap-1.5">
                                <flux:button type="button" size="sm" variant="filled" icon="minus" x-on:click="step('{{ $field }}', -{{ $stepBy }}, {{ $min }}, {{ $max }})" aria-label="Decrease {{ strtolower($label) }}" />
                                <flux:input wire:model="{{ $field }}" type="number" min="{{ $min }}" max="{{ $max }}" class="text-center [&_input]:text-center" />
                                <flux:button type="button" size="sm" variant="filled" icon="plus" x-on:click="step('{{ $field }}', {{ $stepBy }}, {{ $min }}, {{ $max }})" aria-label="Increase {{ strtolower($label) }}" />
                            </div>
                            <flux:error name="{{ $field }}" />
                        </flux:field>
                    @endforeach
                </div>

                <flux:field>
                    <flux:label>Weight (lbs)</flux:label>
                    <div class="flex items-center gap-1.5">
                        <flux:button type="button" size="sm" variant="filled" icon="minus" x-on:click="step('weight', -2.5, 0, 2000)" aria-label="Decrease weight by 2.5 lbs" />
                        <flux:input wire:model="weight" type="number" min="0" step="2.5" class="[&_input]:text-center" />
                        <flux:button type="button" size="sm" variant="filled" icon="plus" x-on:click="step('weight', 2.5, 0, 2000)" aria-label="Increase weight by 2.5 lbs" />
                    </div>
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Add plates">
                        @foreach([5, 10, 25, 45] as $plate)
                            <button type="button" x-on:click="step('weight', {{ $plate * 2 }}, 0, 2000)"
                                class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-medium text-zinc-700 transition hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-800 focus-visible:outline-2 focus-visible:outline-emerald-500 active:scale-95 dark:border-white/10 dark:text-zinc-300 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-300"
                            >+{{ $plate }} × 2</button>
                        @endforeach
                    </div>
                    <flux:error name="weight" />
                </flux:field>

                <div class="flex items-center justify-between rounded-xl bg-zinc-50 px-4 py-3 text-sm dark:bg-white/5" aria-live="polite">
                    <span class="text-zinc-600 dark:text-zinc-400">Total volume</span>
                    <span class="font-bold tabular-nums" x-text="(Number($wire.sets) * Number($wire.reps) * Number($wire.weight)).toLocaleString() + ' lbs'"></span>
                </div>

                <flux:button type="submit" variant="primary" icon="check" class="w-full">
                    <span wire:loading.remove wire:target="logWorkout">Log Workout</span>
                    <span wire:loading wire:target="logWorkout">Saving…</span>
                </flux:button>
            </form>
        </flux:card>

        {{-- Recent Workouts --}}
        <flux:card class="!rounded-2xl lg:col-span-3">
            <div class="mb-5 flex items-center justify-between gap-3">
                <flux:heading size="lg" level="2">Recent Workouts (Last 10)</flux:heading>
                <flux:button :href="route('schedule')" wire:navigate size="sm" variant="ghost" icon="calendar-days">Schedule</flux:button>
            </div>

            @if($this->recentWorkouts->isEmpty())
                <x-ui.empty-state icon="bolt" title="No workouts logged yet. Get after it!" description="Your training history will appear here as soon as you log your first set." />
            @else
                <div class="space-y-5">
                    @foreach($this->recentWorkouts->groupBy(fn ($workout) => $workout->created_at->toDateString()) as $date => $workouts)
                        @php $day = \Illuminate\Support\Carbon::parse($date); @endphp
                        <section aria-label="{{ $day->format('l j F') }}">
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-widest text-zinc-600 dark:text-zinc-400">
                                {{ $day->isToday() ? 'Today' : ($day->isYesterday() ? 'Yesterday' : $day->format('D j M')) }}
                            </h3>
                            <ul class="space-y-2">
                                @foreach($workouts as $workout)
                                    <li wire:key="workout-{{ $workout->id }}" class="group flex items-center gap-3 rounded-xl border border-zinc-200 p-3 transition hover:border-emerald-300 dark:border-white/10 dark:hover:border-emerald-500/40">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300" aria-hidden="true">
                                            <flux:icon.bolt variant="solid" class="size-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-medium">{{ $workout->equipment }}</p>
                                            <p class="text-sm tabular-nums text-zinc-600 dark:text-zinc-400">
                                                {{ $workout->sets }} sets × {{ $workout->reps }} reps · <span class="font-semibold text-zinc-900 dark:text-white">{{ $workout->weight }} lbs</span>
                                            </p>
                                        </div>
                                        <span class="hidden text-xs text-zinc-500 sm:inline dark:text-zinc-400">{{ $workout->created_at->format('d M') }}</span>
                                        <flux:button
                                            wire:click="deleteWorkout({{ $workout->id }})"
                                            wire:confirm="Remove this workout entry?"
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            aria-label="Remove {{ $workout->equipment }} entry"
                                        />
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            @endif
        </flux:card>

    </div>
</div>
