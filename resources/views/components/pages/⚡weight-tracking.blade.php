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

        $previousWeight = $this->recentWeights->first()?->weight_in_lbs;

        BodyWeight::create([
            'user_id' => auth()->id(),
            'weight_in_lbs' => $this->weightInLbs,
        ]);

        $loggedWeight = (float) $this->weightInLbs;
        $this->reset('weightInLbs');
        $this->showSuccess = true;
        unset($this->recentWeights, $this->recentLossPerDay);
        $this->resetChartComputedProperties();

        $this->dispatch('celebrate', message: $this->weighInMessage($previousWeight, $loggedWeight));
    }

    /**
     * Builds an encouraging message comparing the new weigh-in with the previous one.
     */
    private function weighInMessage(?float $previousWeight, float $loggedWeight): string
    {
        if ($previousWeight === null) {
            return 'First weigh-in logged — the journey starts here!';
        }

        $difference = round($loggedWeight - $previousWeight, 1);

        return match (true) {
            $difference < 0 => 'Down '.abs($difference).' lbs since your last weigh-in!',
            $difference > 0 => 'Weigh-in logged — consistency is what counts.',
            default => 'Holding steady — weigh-in logged!',
        };
    }
};
?>

<div class="af-stagger flex flex-col gap-6">
    @php
        $latestWeight = $this->recentWeights->first()?->weight_in_lbs;
        $stats = $this->goalStats;
        $journeyPercent = null;

        if ($this->bodyWeightGoal && $latestWeight !== null && $stats['current_weight'] != $stats['end_goal']) {
            $journeyPercent = max(0, min(100, (($stats['current_weight'] - $latestWeight) / ($stats['current_weight'] - $stats['end_goal'])) * 100));
        }
    @endphp

    <x-ui.page-header icon="scale" eyebrow="Progress" title="Weight Tracking" subtitle="Log your weight and monitor progress towards your goals.">
        <x-slot:actions>
            <flux:button :href="route('weight.goals')" wire:navigate variant="ghost" icon="pencil-square">
                Edit Goals
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">

        {{-- Log Weight Form --}}
        <flux:card class="!rounded-2xl">
            <flux:heading size="lg" level="2" class="mb-1">Log Today's Weight</flux:heading>
            <flux:text class="mb-5">Weigh in at the same time each day for the most accurate trend.</flux:text>

            @if($showSuccess)
                <x-ui.success-banner class="mb-4" wire:key="weight-success-{{ $this->recentWeights->first()?->id }}">Weight recorded!</x-ui.success-banner>
            @endif

            <form wire:submit="logWeight" class="space-y-4">
                <flux:field>
                    <flux:label>Weight (lbs)</flux:label>
                    <flux:input wire:model="weightInLbs" type="number" min="50" max="1000" step="0.1" placeholder="e.g. 185.5" inputmode="decimal" class="[&_input]:!h-14 [&_input]:!text-2xl [&_input]:font-bold [&_input]:tabular-nums" />
                    <flux:error name="weightInLbs" />
                </flux:field>
                <flux:button type="submit" variant="primary" icon="check" class="w-full">Record Weight</flux:button>
            </form>

            @if($latestWeight !== null)
                <div class="mt-5 flex items-center justify-between rounded-xl bg-zinc-50 px-4 py-3 text-sm dark:bg-white/5">
                    <span class="text-zinc-600 dark:text-zinc-400">Last weigh-in</span>
                    <span class="font-semibold tabular-nums">{{ round($latestWeight, 1) }} lbs · {{ $this->recentWeights->first()->created_at->diffForHumans() }}</span>
                </div>
            @endif
        </flux:card>

        {{-- Goal journey --}}
        <div class="flex flex-col gap-6 lg:col-span-2">
            @if($this->bodyWeightGoal)
                <flux:card class="!rounded-2xl">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                        <x-ui.ring :percent="$journeyPercent ?? 0" :size="128" :stroke="11" color="text-emerald-500" label="Progress towards end goal">
                            <span class="text-2xl font-bold tabular-nums">{{ $journeyPercent !== null ? round($journeyPercent) . '%' : '—' }}</span>
                            <span class="text-[11px] text-zinc-600 dark:text-zinc-400">to end goal</span>
                        </x-ui.ring>
                        <div class="min-w-0 flex-1">
                            <flux:heading size="lg" level="2">Your journey</flux:heading>
                            <flux:text class="mb-4">
                                @if($journeyPercent !== null && $journeyPercent >= 100)
                                    Goal smashed! Time to set a new one.
                                @elseif($journeyPercent !== null && $journeyPercent >= 50)
                                    Over halfway there — stay consistent.
                                @else
                                    Every weigh-in moves you closer.
                                @endif
                            </flux:text>
                            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach([
                                    ['Start weight (lbs)', $stats['current_weight']],
                                    ['Milestone target (lbs)', $stats['target_weight']],
                                    ['End goal (lbs)', $stats['end_goal']],
                                    ['Days to milestone', $stats['days_remaining']],
                                ] as [$label, $value])
                                    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-white/5">
                                        <dd class="text-xl font-bold tabular-nums">{{ $value }}</dd>
                                        <dt class="text-xs text-zinc-600 dark:text-zinc-400">{{ $label }}</dt>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                </flux:card>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.stat icon="flag" tone="sky"
                        :value="($stats['required_loss_per_day'] > 0 ? $stats['required_loss_per_day'] : '—').' lbs/day'"
                        :label="'Required loss rate to hit milestone by '.\Illuminate\Support\Carbon::parse($stats['milestone_date'])->format('j M Y')" />
                    <x-ui.stat icon="arrow-trending-down" :tone="$this->recentLossPerDay < 0 ? 'emerald' : 'amber'"
                        :value="$this->recentLossPerDay.' lbs/entry'"
                        label="Average recent change (last 10 entries)" />
                </div>
            @else
                <x-ui.empty-state icon="flag" title="No goals set yet." description="Set your body weight goals to unlock journey tracking, milestones and countdowns.">
                    <flux:button :href="route('weight.goals')" wire:navigate variant="primary" icon="flag" size="sm">Set your body weight goals</flux:button>
                </x-ui.empty-state>
            @endif
        </div>
    </div>

    {{-- Trend chart --}}
    <flux:card class="!rounded-2xl space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="lg" level="2">Weight Trend</flux:heading>
                <flux:text>
                    {{ $this->chartRangeLabel }} · {{ $this->chartRangeResolution }}
                </flux:text>
            </div>
            <flux:badge class="self-start" color="{{ $this->chartTrend['color'] }}" icon="{{ $this->chartTrend['direction'] === 'Gaining' ? 'arrow-trending-up' : ($this->chartTrend['direction'] === 'Losing' ? 'arrow-trending-down' : 'minus') }}">
                {{ $this->chartTrend['direction'] }}
                @if($this->chartTrend['change'] !== 0)
                    ({{ $this->chartTrend['change'] > 0 ? '+' : '' }}{{ $this->chartTrend['change'] }} lbs)
                @endif
            </flux:badge>
        </div>

        <div class="inline-flex flex-wrap gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-white/5" role="group" aria-label="Chart range">
            @foreach($this->chartRangeButtons as $button)
                <button type="button" wire:click="setChartRange('{{ $button['value'] }}')"
                    aria-pressed="{{ $chartRange === $button['value'] ? 'true' : 'false' }}"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-emerald-500 {{ $chartRange === $button['value'] ? 'bg-white text-zinc-900 shadow-sm dark:bg-white/15 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
                >
                    {{ $button['button_label'] }}
                </button>
            @endforeach
        </div>

        @if(empty($this->chartData))
            <x-ui.empty-state icon="chart-bar" title="No chart data available for this period yet." description="Log a few weigh-ins and your trend line will appear here." />
        @else
            <flux:chart :value="$this->chartData" wire:key="weight-chart-{{ $chartRange }}">
                <flux:chart.viewport class="aspect-[2/1] sm:aspect-[3/1]">
                    <flux:chart.svg>
                        <flux:chart.line field="weight" class="text-emerald-500 dark:text-emerald-400" curve="none" />
                        <flux:chart.area field="weight" class="text-emerald-200/50 dark:text-emerald-400/15" curve="none" />
                        <flux:chart.point field="weight" class="text-emerald-500 dark:text-emerald-400" r="3" />
                        <flux:chart.axis axis="x" field="label" tick-count="5">
                            <flux:chart.axis.tick />
                            <flux:chart.axis.line />
                        </flux:chart.axis>
                        <flux:chart.axis axis="y" tick-start="min" tick-count="4">
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

    {{-- History --}}
    <flux:card class="!rounded-2xl">
        <flux:heading size="lg" level="2" class="mb-4">Recent History (Last 10)</flux:heading>

        @if($this->recentWeights->isEmpty())
            <x-ui.empty-state icon="scale" title="No weight entries yet. Log your first one!" />
        @else
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach($this->recentWeights as $i => $entry)
                    @php
                        $prev = $this->recentWeights[$i + 1] ?? null;
                        $change = $prev ? round($entry->weight_in_lbs - $prev->weight_in_lbs, 1) : null;
                    @endphp
                    <li wire:key="weight-{{ $entry->id }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3 dark:border-white/10">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-xs font-semibold text-zinc-600 dark:bg-white/10 dark:text-zinc-300" aria-hidden="true">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold tabular-nums">{{ round($entry->weight_in_lbs, 1) }} lbs</p>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $entry->created_at->format('d M Y') }}</p>
                        </div>
                        @if($change !== null)
                            <flux:badge size="sm" color="{{ $change < 0 ? 'green' : ($change > 0 ? 'red' : 'zinc') }}">
                                {{ $change > 0 ? '+' : '' }}{{ $change }}
                            </flux:badge>
                        @else
                            <span class="text-zinc-500 dark:text-zinc-400" aria-label="No previous entry">—</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </flux:card>

</div>
