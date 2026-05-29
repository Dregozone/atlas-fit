<?php

use App\Models\BodyWeight;
use App\Models\BodyWeightGoal;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Weight Tracking')] class extends Component {
    private const MAINTAINING_THRESHOLD_LBS = 0.3;

    #[Validate('required|numeric|min:50|max:1000')]
    public float $weightInLbs = 0;

    public bool $showSuccess = false;
    public string $chartRange = '1m';

    #[Computed]
    public function recentWeights()
    {
        return BodyWeight::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
    }

    #[Computed]
    public function bodyWeightGoal(): ?object
    {
        return BodyWeightGoal::where('user_id', auth()->id())->first();
    }

    #[Computed]
    public function goalStats(): array
    {
        $goal = $this->bodyWeightGoal;

        if (! $goal) {
            return [
                'current_weight' => 0,
                'end_goal' => 0,
                'target_weight' => 0,
                'milestone_date' => null,
                'days_remaining' => 0,
                'required_loss_per_day' => 0,
            ];
        }

        $daysRemaining = (int) Carbon::now()->diffInDays(Carbon::parse($goal->milestone_date), false);

        $requiredLossPerDay = $daysRemaining > 0
            ? round(($goal->start_weight - $goal->milestone_goal_weight) / $daysRemaining, 2)
            : 0;

        return [
            'current_weight' => round($goal->start_weight, 1),
            'end_goal' => round($goal->end_goal_weight, 1),
            'target_weight' => round($goal->milestone_goal_weight, 1),
            'milestone_date' => $goal->milestone_date,
            'days_remaining' => $daysRemaining,
            'required_loss_per_day' => $requiredLossPerDay,
        ];
    }

    #[Computed]
    public function recentLossPerDay(): float
    {
        $weights = $this->recentWeights;

        if ($weights->count() < 2) {
            return 0;
        }

        $totalDiff = 0;
        $count = 0;

        for ($i = 0; $i < $weights->count() - 1; $i++) {
            $totalDiff += $weights[$i]->weight_in_lbs - $weights[$i + 1]->weight_in_lbs;
            $count++;
        }

        return round($totalDiff / $count, 2);
    }

    #[Computed]
    public function chartData(): array
    {
        $range = $this->chartRangeConfig;

        $dailyAverages = BodyWeight::query()
            ->where('user_id', auth()->id())
            ->when($range['start'], fn ($query) => $query->where('created_at', '>=', $range['start']))
            ->selectRaw('DATE(created_at) as entry_date')
            ->selectRaw('AVG(weight_in_lbs) as average_weight')
            ->groupBy('entry_date')
            ->orderBy('entry_date')
            ->get()
            ->map(fn (BodyWeight $entry) => [
                'date' => $entry->entry_date,
                'weight' => round((float) $entry->average_weight, 1),
            ])
            ->values()
            ->toArray();

        $chunkDays = $range['chunk_days'];

        if ($chunkDays <= 1) {
            return collect($dailyAverages)
                ->map(function (array $entry) {
                    $date = Carbon::parse($entry['date']);

                    return [
                        'label' => $date->format('j M'),
                        'weight' => $entry['weight'],
                        'date' => $date->format('j M Y'),
                    ];
                })
                ->toArray();
        }

        return collect($dailyAverages)
            ->chunk($chunkDays)
            ->map(function ($chunk) {
                $start = Carbon::parse($chunk->first()['date']);
                $end = Carbon::parse($chunk->last()['date']);

                return [
                    'label' => $start->isSameDay($end)
                        ? $start->format('j M')
                        : ($start->year === $end->year
                            ? $start->format('j M').' - '.$end->format('j M')
                            : $start->format('j M Y').' - '.$end->format('j M Y')),
                    'weight' => round($chunk->avg('weight'), 1),
                    'date' => $end->format('j M Y'),
                ];
            })
            ->values()
            ->toArray();
    }

    #[Computed]
    public function chartRangeConfig(): array
    {
        $ranges = $this->chartRanges();

        return $ranges[$this->chartRange] ?? $ranges['1m'];
    }

    #[Computed]
    public function chartRangeButtons(): array
    {
        return collect($this->chartRanges())
            ->map(fn (array $range, string $value) => [
                'value' => $value,
                'button_label' => $range['button_label'],
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function chartTrend(): array
    {
        if (count($this->chartData) < 2) {
            return [
                'direction' => 'Not enough data yet',
                'change' => 0,
                'color' => 'zinc',
            ];
        }

        $first = $this->chartData[0]['weight'];
        $last = $this->chartData[count($this->chartData) - 1]['weight'];
        $change = round($last - $first, 1);

        if (abs($change) <= self::MAINTAINING_THRESHOLD_LBS) {
            return [
                'direction' => 'Maintaining',
                'change' => $change,
                'color' => 'zinc',
            ];
        }

        if ($change > 0) {
            return [
                'direction' => 'Gaining',
                'change' => $change,
                'color' => 'red',
            ];
        }

        return [
            'direction' => 'Losing',
            'change' => $change,
            'color' => 'green',
        ];
    }

    #[Computed]
    public function chartRangeLabel(): string
    {
        return $this->chartRangeConfig['label'];
    }

    #[Computed]
    public function chartRangeResolution(): string
    {
        return $this->chartRangeConfig['resolution'];
    }

    public function setChartRange(string $range): void
    {
        if (! auth()->check()) {
            return;
        }

        if (! array_key_exists($range, $this->chartRanges())) {
            return;
        }

        $this->chartRange = $range;
        $this->resetChartComputedProperties();
    }

    private function chartRanges(): array
    {
        $referenceNow = Carbon::now();

        return [
            '1m' => [
                'button_label' => '1M',
                'label' => 'Last month',
                'start' => $referenceNow->copy()->subMonth()->startOfDay(),
                'chunk_days' => 1,
                'resolution' => 'daily average points',
            ],
            '3m' => [
                'button_label' => '3M',
                'label' => 'Last 3 months',
                'start' => $referenceNow->copy()->subMonths(3)->startOfDay(),
                'chunk_days' => 3,
                'resolution' => '3-day average points',
            ],
            '6m' => [
                'button_label' => '6M',
                'label' => 'Last 6 months',
                'start' => $referenceNow->copy()->subMonths(6)->startOfDay(),
                'chunk_days' => 7,
                'resolution' => 'weekly average points',
            ],
            '1y' => [
                'button_label' => '1Y',
                'label' => 'Last year',
                'start' => $referenceNow->copy()->subYear()->startOfDay(),
                'chunk_days' => 14,
                'resolution' => '2-week average points',
            ],
            'all' => [
                'button_label' => 'Since Joining',
                'label' => 'Since joining',
                'start' => null,
                'chunk_days' => 30,
                'resolution' => 'monthly average points',
            ],
        ];
    }

    private function resetChartComputedProperties(): void
    {
        unset($this->chartRangeConfig, $this->chartRangeLabel, $this->chartRangeResolution, $this->chartData, $this->chartTrend);
    }

    public function logWeight(): void
    {
        $this->validate();

        BodyWeight::create([
            'user_id' => auth()->id(),
            'weight_in_lbs' => $this->weightInLbs,
        ]);

        $this->reset('weightInLbs');
        $this->showSuccess = true;
        unset($this->recentWeights, $this->recentLossPerDay);
        $this->resetChartComputedProperties();
    }
};
?>

    <div class="flex flex-col gap-6 p-6">

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Weight Tracking</flux:heading>
                <flux:text class="text-zinc-500">Log your weight and monitor progress towards your goals.</flux:text>
            </div>
            <flux:button :href="route('weight.goals')" wire:navigate variant="ghost" icon="pencil-square">
                Edit Goals
            </flux:button>
        </div>

        {{-- Goal Summary --}}
        @if($this->bodyWeightGoal)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <flux:card class="text-center">
                    <flux:heading size="xl">{{ $this->goalStats['current_weight'] }}</flux:heading>
                    <flux:text class="text-zinc-500">Start weight (lbs)</flux:text>
                </flux:card>
                <flux:card class="text-center">
                    <flux:heading size="xl">{{ $this->goalStats['target_weight'] }}</flux:heading>
                    <flux:text class="text-zinc-500">Milestone target (lbs)</flux:text>
                </flux:card>
                <flux:card class="text-center">
                    <flux:heading size="xl">{{ $this->goalStats['end_goal'] }}</flux:heading>
                    <flux:text class="text-zinc-500">End goal (lbs)</flux:text>
                </flux:card>
                <flux:card class="text-center">
                    <flux:heading size="xl">{{ $this->goalStats['days_remaining'] }}</flux:heading>
                    <flux:text class="text-zinc-500">Days to milestone</flux:text>
                </flux:card>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:card class="flex items-center gap-4">
                    <flux:icon.arrow-trending-down class="size-8 text-blue-500" />
                    <div>
                        <flux:heading size="lg">{{ $this->goalStats['required_loss_per_day'] > 0 ? $this->goalStats['required_loss_per_day'] : '—' }} lbs/day</flux:heading>
                        <flux:text class="text-zinc-500">Required loss rate to hit milestone by {{ $this->goalStats['milestone_date'] }}</flux:text>
                    </div>
                </flux:card>
                <flux:card class="flex items-center gap-4">
                    <flux:icon.chart-bar class="size-8 {{ $this->recentLossPerDay < 0 ? 'text-green-500' : 'text-zinc-400' }}" />
                    <div>
                        <flux:heading size="lg">{{ $this->recentLossPerDay }} lbs/entry</flux:heading>
                        <flux:text class="text-zinc-500">Average recent change (last 10 entries)</flux:text>
                    </div>
                </flux:card>
            </div>
        @else
            <flux:callout icon="information-circle">
                <flux:callout.text>
                    No goals set yet. <flux:link href="{{ route('weight.goals') }}" wire:navigate>Set your body weight goals</flux:link> to see progress tracking.
                </flux:callout.text>
            </flux:callout>
        @endif

        <flux:card class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <flux:heading size="lg">Weight Trend</flux:heading>
                    <flux:text class="text-zinc-500">
                        {{ $this->chartRangeLabel }} · {{ $this->chartRangeResolution }}
                    </flux:text>
                </div>
                <flux:badge color="{{ $this->chartTrend['color'] }}">
                    {{ $this->chartTrend['direction'] }}
                    @if($this->chartTrend['change'] !== 0)
                        ({{ $this->chartTrend['change'] > 0 ? '+' : '' }}{{ $this->chartTrend['change'] }} lbs)
                    @endif
                </flux:badge>
            </div>

            <div class="flex flex-wrap gap-2" role="group" aria-label="Chart range">
                @foreach($this->chartRangeButtons as $button)
                    <flux:button size="sm" variant="{{ $chartRange === $button['value'] ? 'primary' : 'ghost' }}" wire:click="setChartRange('{{ $button['value'] }}')">
                        {{ $button['button_label'] }}
                    </flux:button>
                @endforeach
            </div>

            @if(empty($this->chartData))
                <flux:text class="text-zinc-500">No chart data available for this period yet.</flux:text>
            @else
                <flux:chart :value="$this->chartData">
                    <flux:chart.viewport class="aspect-[3/1]">
                        <flux:chart.svg>
                            <flux:chart.line field="weight" class="text-blue-500 dark:text-blue-400" curve="none" />
                            <flux:chart.area field="weight" class="text-blue-200/40 dark:text-blue-400/20" curve="none" />
                            <flux:chart.axis axis="x" field="label">
                                <flux:chart.axis.tick />
                                <flux:chart.axis.line />
                            </flux:chart.axis>
                            <flux:chart.axis axis="y">
                                <flux:chart.axis.grid />
                                <flux:chart.axis.tick />
                            </flux:chart.axis>
                            <flux:chart.cursor />
                        </flux:chart.svg>
                        <flux:chart.tooltip>
                            <flux:chart.tooltip.heading field="date" />
                            <flux:chart.tooltip.value field="weight" label="Weight (lbs)" />
                        </flux:chart.tooltip>
                    </flux:chart.viewport>
                </flux:chart>
            @endif
        </flux:card>

        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Log Weight Form --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Log Today's Weight</flux:heading>

                @if($showSuccess)
                    <flux:callout icon="check-circle" color="green" class="mb-4">
                        <flux:callout.text>Weight recorded!</flux:callout.text>
                    </flux:callout>
                @endif

                <form wire:submit="logWeight" class="space-y-4">
                    <flux:field>
                        <flux:label>Weight (lbs)</flux:label>
                        <flux:input wire:model="weightInLbs" type="number" min="50" max="1000" step="0.1" placeholder="e.g. 185.5" />
                        <flux:error name="weightInLbs" />
                    </flux:field>
                    <flux:button type="submit" variant="primary" class="w-full">Record Weight</flux:button>
                </form>
            </flux:card>

            {{-- History --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Recent History (Last 10)</flux:heading>

                @if($this->recentWeights->isEmpty())
                    <flux:text class="text-zinc-500">No weight entries yet. Log your first one!</flux:text>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>#</flux:table.column>
                            <flux:table.column>Weight (lbs)</flux:table.column>
                            <flux:table.column>Date</flux:table.column>
                            <flux:table.column>Change</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->recentWeights as $i => $entry)
                                @php
                                    $prev = $this->recentWeights[$i + 1] ?? null;
                                    $change = $prev ? round($entry->weight_in_lbs - $prev->weight_in_lbs, 1) : null;
                                @endphp
                                <flux:table.row>
                                    <flux:table.cell class="text-zinc-400">{{ $i + 1 }}</flux:table.cell>
                                    <flux:table.cell class="font-medium">{{ round($entry->weight_in_lbs, 1) }}</flux:table.cell>
                                    <flux:table.cell class="text-sm text-zinc-400">{{ $entry->created_at->format('d M Y') }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if($change !== null)
                                            <flux:badge color="{{ $change < 0 ? 'green' : ($change > 0 ? 'red' : 'zinc') }}">
                                                {{ $change > 0 ? '+' : '' }}{{ $change }}
                                            </flux:badge>
                                        @else
                                            <flux:text class="text-zinc-400">—</flux:text>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </flux:card>

        </div>

    </div>
