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
    }
};
?>

    <div class="flex flex-col gap-6 p-6">

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Body Weight Goals</flux:heading>
                <flux:text class="text-zinc-500">Define your start weight, milestone target, and end goal.</flux:text>
            </div>
            <flux:button :href="route('weight')" wire:navigate variant="ghost" icon="arrow-left">
                Back to Weight
            </flux:button>
        </div>

        <flux:card class="max-w-xl">
            <flux:heading size="lg" class="mb-4">{{ $isEdit ? 'Update' : 'Set' }} Goals</flux:heading>

            @if($showSuccess)
                <flux:callout icon="check-circle" color="green" class="mb-4">
                    <flux:callout.text>Goals saved successfully!</flux:callout.text>
                </flux:callout>
            @endif

            <form wire:submit="saveGoals" class="space-y-5">

                <flux:field>
                    <flux:label>Start Weight (lbs)</flux:label>
                    <flux:description>Your current / starting weight in pounds.</flux:description>
                    <flux:input wire:model="startWeight" type="number" min="50" max="1000" step="0.1" />
                    <flux:error name="startWeight" />
                </flux:field>

                <flux:field>
                    <flux:label>End Goal Weight (lbs)</flux:label>
                    <flux:description>Your long-term target weight.</flux:description>
                    <flux:input wire:model="endGoalWeight" type="number" min="50" max="1000" step="0.1" />
                    <flux:error name="endGoalWeight" />
                </flux:field>

                <flux:field>
                    <flux:label>Milestone Target Weight (lbs)</flux:label>
                    <flux:description>An intermediate milestone to hit by the date below.</flux:description>
                    <flux:input wire:model="milestoneGoalWeight" type="number" min="50" max="1000" step="0.1" />
                    <flux:error name="milestoneGoalWeight" />
                </flux:field>

                <flux:field>
                    <flux:label>Milestone Date</flux:label>
                    <flux:description>The date you want to hit your milestone weight by.</flux:description>
                    <flux:input wire:model="milestoneDate" type="date" />
                    <flux:error name="milestoneDate" />
                </flux:field>

                <flux:button type="submit" variant="primary" class="w-full">
                    {{ $isEdit ? 'Update Goals' : 'Save Goals' }}
                </flux:button>
            </form>
        </flux:card>

    </div>
