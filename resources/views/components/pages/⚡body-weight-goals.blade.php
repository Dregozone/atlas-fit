<?php

use App\Models\BodyWeightGoal;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Body Weight Goals')] class extends Component {

    #[Validate('required|numeric|min:50|max:1000')]
    public float $startWeight = 0;

    #[Validate('required|numeric|min:50|max:1000')]
    public float $endGoalWeight = 0;

    #[Validate('required|numeric|min:50|max:1000')]
    public float $milestoneGoalWeight = 0;

    #[Validate('required|date|after:today')]
    public string $milestoneDate = '';

    public bool $showSuccess = false;
    public bool $isEdit = false;

    public function mount(): void
    {
        $goal = BodyWeightGoal::where('user_id', auth()->id())->first();

        if ($goal) {
            $this->isEdit = true;
            $this->startWeight = $goal->start_weight;
            $this->endGoalWeight = $goal->end_goal_weight;
            $this->milestoneGoalWeight = $goal->milestone_goal_weight;
            $this->milestoneDate = $goal->milestone_date;
        } else {
            $this->milestoneDate = now()->addMonths(3)->format('Y-m-d');
        }
    }

    public function saveGoals(): void
    {
        $this->validate();

        BodyWeightGoal::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'start_weight' => $this->startWeight,
                'end_goal_weight' => $this->endGoalWeight,
                'milestone_goal_weight' => $this->milestoneGoalWeight,
                'milestone_date' => $this->milestoneDate,
            ]
        );

        $this->isEdit = true;
        $this->showSuccess = true;

        $this->dispatch('celebrate', message: 'Goals locked in — time to make it happen!', big: true);
    }
};
?>

<div class="af-stagger flex flex-col gap-6">

    <x-ui.page-header icon="flag" eyebrow="Progress" title="Body Weight Goals" subtitle="Define your start weight, milestone target, and end goal.">
        <x-slot:actions>
            <flux:button :href="route('weight')" wire:navigate variant="ghost" icon="arrow-left">
                Back to Weight
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid items-start gap-6 lg:grid-cols-5">
        <flux:card class="!rounded-2xl lg:col-span-3">
            <flux:heading size="lg" level="2" class="mb-5">{{ $isEdit ? 'Update' : 'Set' }} Goals</flux:heading>

            @if($showSuccess)
                <x-ui.success-banner class="mb-5" title="Goals saved successfully!">Your new targets are live on the weight page.</x-ui.success-banner>
            @endif

            <form wire:submit="saveGoals" class="space-y-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Start Weight (lbs)</flux:label>
                        <flux:description>Your current / starting weight in pounds.</flux:description>
                        <flux:input wire:model="startWeight" type="number" min="50" max="1000" step="0.1" inputmode="decimal" />
                        <flux:error name="startWeight" />
                    </flux:field>

                    <flux:field>
                        <flux:label>End Goal Weight (lbs)</flux:label>
                        <flux:description>Your long-term target weight.</flux:description>
                        <flux:input wire:model="endGoalWeight" type="number" min="50" max="1000" step="0.1" inputmode="decimal" />
                        <flux:error name="endGoalWeight" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Milestone Target Weight (lbs)</flux:label>
                        <flux:description>An intermediate milestone to hit by the date below.</flux:description>
                        <flux:input wire:model="milestoneGoalWeight" type="number" min="50" max="1000" step="0.1" inputmode="decimal" />
                        <flux:error name="milestoneGoalWeight" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Milestone Date</flux:label>
                        <flux:description>The date you want to hit your milestone weight by.</flux:description>
                        <flux:input wire:model="milestoneDate" type="date" />
                        <flux:error name="milestoneDate" />
                    </flux:field>
                </div>

                <flux:button type="submit" variant="primary" icon="flag" class="w-full">
                    {{ $isEdit ? 'Update Goals' : 'Save Goals' }}
                </flux:button>
            </form>
        </flux:card>

        <flux:card class="!rounded-2xl lg:col-span-2">
            <flux:heading size="lg" level="2" class="mb-4">How it works</flux:heading>
            <ol class="space-y-4">
                @foreach([
                    ['flag', 'Start', 'Where you are today — your baseline.'],
                    ['map-pin', 'Milestone', 'A realistic checkpoint with a deadline keeps you accountable.'],
                    ['trophy', 'End goal', 'The long-term destination. Celebrate every milestone on the way.'],
                ] as [$icon, $title, $copy])
                    <li class="flex gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300" aria-hidden="true">
                            <flux:icon :icon="$icon" variant="solid" class="size-5" />
                        </span>
                        <div>
                            <p class="font-semibold">{{ $title }}</p>
                            <flux:text class="text-sm">{{ $copy }}</flux:text>
                        </div>
                    </li>
                @endforeach
            </ol>
        </flux:card>
    </div>
</div>
