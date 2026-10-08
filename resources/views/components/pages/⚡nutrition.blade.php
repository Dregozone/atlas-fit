<?php

use App\Models\MealItem;
use App\Models\Consumed;
use App\Services\MacroCalculator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Nutrition')] class extends Component {
    private const QUICK_ADD_MIN = 1;
    private const QUICK_ADD_MAX = 10;

    // Add new meal item form
    public bool $showAddItemForm = false;

    #[Validate('required|string|max:255')]
    public string $newItemName = '';

    #[Validate('required|numeric|min:0|max:500')]
    public float $newItemCarbs = 0;

    #[Validate('required|numeric|min:0|max:500')]
    public float $newItemProtein = 0;

    #[Validate('required|numeric|min:0|max:500')]
    public float $newItemFat = 0;

    public bool $itemAddedSuccess = false;
    public bool $quickAddSuccess = false;
    public string $quickAddName = '';
    public int $quickAddQuantity = 1;
    public array $quickAddQuantities = [];

    #[Computed]
    public function macroGoals(): array
    {
        $user = auth()->user();

        if (! $user->body_weight_lbs || ! $user->fitness_goal) {
            return ['carbs' => null, 'protein' => null, 'fat' => null, 'calories' => null, 'goal' => null];
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
            'goal' => $user->fitness_goal,
        ];
    }

    #[Computed]
    public function foodItems()
    {
        $items = MealItem::where('is_active', true)->orderBy('name')->get();

        foreach ($items as $item) {
            if (! array_key_exists($item->id, $this->quickAddQuantities)) {
                $this->quickAddQuantities[$item->id] = self::QUICK_ADD_MIN;
            }
        }

        return $items;
    }

    #[Computed]
    public function remainingMacros(): array
    {
        $goals = $this->macroGoals;
        $totals = $this->todayTotals;

        return [
            'protein'  => $goals['protein']  !== null ? (float) $goals['protein']  - (float) $totals->protein  : null,
            'carbs'    => $goals['carbs']    !== null ? (float) $goals['carbs']    - (float) $totals->carbs    : null,
            'fat'      => $goals['fat']      !== null ? (float) $goals['fat']      - (float) $totals->fat      : null,
            'calories' => $goals['calories'] !== null ? (float) $goals['calories'] - (float) $totals->calories : null,
        ];
    }

    #[Computed]
    public function catalogData()
    {
        $goals  = $this->macroGoals;
        $totals = $this->todayTotals;

        /**
         * Returns the traffic-light state for a single macro on a food item.
         * If eating the item would push the projected daily total past 75% of
         * the goal → red; past 50% → amber; otherwise → green.
         */
        $trafficState = static function (float $itemValue, float $currentTotal, ?float $dailyGoal): string {
            if ($dailyGoal === null || $dailyGoal <= 0) {
                return 'none';
            }
            $projected = $currentTotal + $itemValue;
            $percentage = $projected / $dailyGoal;
            if ($percentage > 1.00) {
                return 'red';
            }
            if ($percentage > 0.80) {
                return 'amber';
            }
            return 'green';
        };

        $trafficClass = static function (string $state): string {
            return match ($state) {
                'green' => 'text-green-700 dark:text-green-400 font-semibold',
                'amber' => 'text-amber-700 dark:text-amber-400 font-semibold',
                'red'   => 'text-red-600 dark:text-red-400 font-semibold',
                default => '',
            };
        };

        $stateScore = static fn (string $state): int => match ($state) {
            'green' => 2,
            'amber' => 1,
            default => 0,
        };

        return $this->foodItems
            ->map(function ($item) use ($goals, $totals, $trafficState, $trafficClass, $stateScore) {
                $proteinState  = $trafficState((float) $item->protein,  (float) $totals->protein,  $goals['protein']  !== null ? (float) $goals['protein']  : null);
                $carbsState    = $trafficState((float) $item->carbs,    (float) $totals->carbs,    $goals['carbs']    !== null ? (float) $goals['carbs']    : null);
                $fatState      = $trafficState((float) $item->fat,      (float) $totals->fat,      $goals['fat']      !== null ? (float) $goals['fat']      : null);
                $caloriesState = $trafficState((float) $item->calories, (float) $totals->calories, $goals['calories'] !== null ? (float) $goals['calories'] : null);

                $score = $stateScore($proteinState) + $stateScore($carbsState)
                       + $stateScore($fatState)     + $stateScore($caloriesState);

                return (object) [
                    'id'            => $item->id,
                    'name'          => $item->name,
                    'protein'       => $item->protein,
                    'carbs'         => $item->carbs,
                    'fat'           => $item->fat,
                    'calories'      => $item->calories,
                    'proteinClass'  => $trafficClass($proteinState),
                    'carbsClass'    => $trafficClass($carbsState),
                    'fatClass'      => $trafficClass($fatState),
                    'caloriesClass' => $trafficClass($caloriesState),
                    'score'         => $score,
                ];
            })
            ->sortByDesc(fn ($item) => $item->score)
            ->values();
    }

    #[Computed]
    public function todayConsumed()
    {
        return Consumed::query()
            ->join('meal_items', 'consumeds.meal_item_id', '=', 'meal_items.id')
            ->where('consumeds.user_id', auth()->id())
            ->whereDate('consumeds.created_at', Carbon::today())
            ->selectRaw('
                meal_items.name,
                SUM(consumeds.quantity) AS quantity,
                SUM(consumeds.quantity * meal_items.carbs) AS carbs,
                SUM(consumeds.quantity * meal_items.protein) AS protein,
                SUM(consumeds.quantity * meal_items.fat) AS fat,
                SUM(consumeds.quantity * meal_items.calories) AS calories
            ')
            ->groupBy('meal_items.name')
            ->get();
    }

    #[Computed]
    public function todayTotals(): object
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
    public function calculatedCalories(): float
    {
        return round((($this->newItemProtein ?? 0) * 4) + (($this->newItemCarbs ?? 0) * 4) + (($this->newItemFat ?? 0) * 9), 1);
    }

    public function addMealItem(): void
    {
        $this->validateOnly('newItemName');
        $this->validateOnly('newItemCarbs');
        $this->validateOnly('newItemProtein');
        $this->validateOnly('newItemFat');

        $calories = $this->calculatedCalories;

        MealItem::create([
            'name' => $this->newItemName,
            'carbs' => round($this->newItemCarbs, 1),
            'protein' => round($this->newItemProtein, 1),
            'fat' => round($this->newItemFat, 1),
            'calories' => $calories,
            'is_active' => true,
        ]);

        $this->reset(['newItemName', 'newItemCarbs', 'newItemProtein', 'newItemFat']);
        $this->itemAddedSuccess = true;
        unset($this->foodItems, $this->catalogData);

        $this->dispatch('celebrate', message: 'New food added to your catalogue!');
    }

    public function quickAdd(int $itemId): void
    {
        $item = MealItem::findOrFail($itemId, ['id', 'name']);
        $quantity = max(
            self::QUICK_ADD_MIN,
            min(self::QUICK_ADD_MAX, (int) ($this->quickAddQuantities[$itemId] ?? self::QUICK_ADD_MIN))
        );

        Consumed::create([
            'user_id'      => auth()->id(),
            'meal_item_id' => $itemId,
            'quantity'     => $quantity,
        ]);

        $this->quickAddSuccess = true;
        $this->quickAddName = $item->name;
        $this->quickAddQuantity = $quantity;
        $this->quickAddQuantities[$itemId] = self::QUICK_ADD_MIN;
        unset($this->todayConsumed, $this->todayTotals, $this->remainingMacros, $this->catalogData);

        $this->dispatch('celebrate', message: "{$item->name} logged — nicely fuelled!");
    }
};
?>

<div class="af-stagger flex flex-col gap-6">
    @php
        $goals = $this->macroGoals;
        $totals = $this->todayTotals;
        $macroMeta = [
            'calories' => ['label' => 'Calories', 'unit' => 'kcal', 'ring' => 'text-emerald-500'],
            'protein' => ['label' => 'Protein', 'unit' => 'g', 'ring' => 'text-sky-500'],
            'carbs' => ['label' => 'Carbs', 'unit' => 'g', 'ring' => 'text-amber-500'],
            'fat' => ['label' => 'Fat', 'unit' => 'g', 'ring' => 'text-rose-500'],
        ];
    @endphp

    <x-ui.page-header icon="fire" eyebrow="Fuel" title="Nutrition" subtitle="Track your daily food intake and hit your macro targets.">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="$set('showAddItemForm', true)">New food</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Macro Goals Summary --}}
    @if($goals['calories'])
        <flux:card class="!rounded-2xl">
            <div class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <flux:heading size="lg" level="2">Daily Targets — {{ $goals['goal'] }}</flux:heading>
                <flux:text class="text-sm">Based on your profile settings</flux:text>
            </div>
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                @foreach($macroMeta as $key => $meta)
                    @php
                        $remaining = $goals[$key] - $totals->$key;
                        $percent = $goals[$key] > 0 ? ($totals->$key / $goals[$key]) * 100 : 0;
                    @endphp
                    <div class="flex flex-col items-center text-center">
                        <x-ui.ring :percent="$percent" :size="104" :stroke="9" :color="$percent > 100 ? 'text-rose-500' : $meta['ring']" :label="$meta['label'].' eaten today'">
                            <span class="text-lg font-bold tabular-nums">{{ round($totals->$key) }}</span>
                            <span class="text-[11px] text-zinc-600 dark:text-zinc-400">/ {{ $goals[$key] }}{{ $meta['unit'] }}</span>
                        </x-ui.ring>
                        <p class="mt-2 text-sm font-semibold">{{ $meta['label'] }}</p>
                        <p class="text-xs {{ $remaining >= 0 ? 'text-zinc-600 dark:text-zinc-400' : 'font-semibold text-rose-600 dark:text-rose-400' }}">
                            {{ $remaining > 0 ? round($remaining) . ' remaining' : abs(round($remaining)) . ' over' }}
                        </p>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @else
        <flux:callout icon="information-circle" color="sky">
            <flux:callout.text>
                Set your body weight and fitness goal in <flux:link href="{{ route('profile.edit') }}" wire:navigate>Profile settings</flux:link> to see personalised macro targets.
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-5">

        {{-- Food Catalogue --}}
        <flux:card class="!rounded-2xl lg:col-span-3" x-data="{
            search: '',
            names: {!! \Illuminate\Support\Js::from($this->catalogData->pluck('name')->map(fn($n) => strtolower($n))->values()) !!},
            limit: 10,
            isVisible(index) {
                const q = this.search.toLowerCase();
                if (q) {
                    return this.names[index].includes(q);
                }
                return index < this.limit;
            },
            get hasResults() {
                const q = this.search.toLowerCase();
                return !q || this.names.some(n => n.includes(q));
            },
            get hiddenCount() {
                return Math.max(0, this.names.length - this.limit);
            },
            step(id, amount) {
                const next = Number($wire.quickAddQuantities[id] ?? 1) + amount;
                $wire.quickAddQuantities[id] = Math.min({{ self::QUICK_ADD_MAX }}, Math.max({{ self::QUICK_ADD_MIN }}, next));
            },
        }">
            <div class="mb-4 flex items-center justify-between gap-3">
                <flux:heading size="lg" level="2">Food Catalogue</flux:heading>
                <flux:text class="text-sm tabular-nums">{{ $this->catalogData->count() }} items</flux:text>
            </div>

            @if($quickAddSuccess)
                <x-ui.success-banner class="mb-4" wire:key="quick-add-{{ $this->todayConsumed->sum('quantity') }}">
                    {{ $quickAddQuantity }} {{ \Illuminate\Support\Str::plural('serving', $quickAddQuantity) }} of <strong>{{ $quickAddName }}</strong> added to today's diary!
                </x-ui.success-banner>
            @endif

            <flux:input x-model="search" icon="magnifying-glass" clearable placeholder="Start typing a food or drink..." aria-label="Search food catalogue" class="mb-3" />
            <flux:text class="mb-3 text-xs md:hidden">Tap an item name to view its full text.</flux:text>

            @if($goals['calories'])
                <details class="group mb-4 rounded-xl bg-zinc-50 px-4 py-3 text-xs dark:bg-white/5">
                    <summary class="flex cursor-pointer list-none items-center gap-2 font-medium text-zinc-700 dark:text-zinc-300">
                        <span class="flex gap-1" aria-hidden="true">
                            <span class="size-2.5 rounded-full bg-emerald-500"></span>
                            <span class="size-2.5 rounded-full bg-amber-500"></span>
                            <span class="size-2.5 rounded-full bg-rose-500"></span>
                        </span>
                        What do the colours mean?
                        <flux:icon.chevron-down variant="micro" class="ms-auto size-4 transition group-open:rotate-180" aria-hidden="true" />
                    </summary>
                    <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                        Colours show how close to your daily target eating this item would take you:
                        <span class="font-semibold text-green-700 dark:text-green-400">Green</span> = projected total stays under 80% of daily goal,
                        <span class="font-semibold text-amber-700 dark:text-amber-400">Amber</span> = would push past 80%,
                        <span class="font-semibold text-red-600 dark:text-red-400">Red</span> = would exceed the daily goal.
                        Items are ordered with the best overall fit at the top.
                    </p>
                </details>
            @endif

            <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach($this->catalogData as $item)
                    <li wire:key="catalog-{{ $item->id }}" x-show="isVisible({{ $loop->index }})" class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <flux:tooltip :content="$item->name" position="top">
                                    <button type="button" class="min-w-0 truncate text-start font-medium focus-visible:outline-2 focus-visible:outline-emerald-500">
                                        {{ $item->name }}
                                    </button>
                                </flux:tooltip>
                                @if($goals['calories'] && $item->score === 8)
                                    <flux:badge size="sm" color="emerald" icon="sparkles" class="shrink-0">Great fit</flux:badge>
                                @endif
                            </div>
                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs tabular-nums text-zinc-600 dark:text-zinc-400">
                                <span class="{{ $item->proteinClass }}">P {{ $item->protein }}g</span>
                                <span class="{{ $item->carbsClass }}">C {{ $item->carbs }}g</span>
                                <span class="{{ $item->fatClass }}">F {{ $item->fat }}g</span>
                                <span class="{{ $item->caloriesClass }}">{{ $item->calories }} kcal</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <flux:button type="button" size="xs" variant="subtle" icon="minus" x-on:click="step({{ $item->id }}, -1)" aria-label="Fewer servings of {{ $item->name }}" />
                            <flux:input
                                wire:model="quickAddQuantities.{{ $item->id }}"
                                type="number"
                                min="{{ self::QUICK_ADD_MIN }}"
                                max="{{ self::QUICK_ADD_MAX }}"
                                step="1"
                                size="sm"
                                aria-label="Qty. for {{ $item->name }}"
                                class="w-14 [&_input]:text-center"
                            />
                            <flux:button type="button" size="xs" variant="subtle" icon="plus" x-on:click="step({{ $item->id }}, 1)" aria-label="More servings of {{ $item->name }}" />
                            <flux:button wire:click="quickAdd({{ $item->id }})" size="sm" variant="primary" icon="plus-circle" class="ms-auto sm:ms-2" aria-label="Add {{ $item->name }} to today's diary">
                                Add
                            </flux:button>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div x-show="!hasResults" x-cloak>
                <x-ui.empty-state icon="magnifying-glass" title="No food items match your search." description="Can't find it? Add it to the catalogue with the New food button.">
                    <flux:button size="sm" icon="plus" wire:click="$set('showAddItemForm', true)">New food</flux:button>
                </x-ui.empty-state>
            </div>

            <div x-show="!search && hiddenCount > 0" class="mt-3 text-center">
                <flux:button variant="ghost" size="sm" icon="chevron-down" x-on:click="limit = names.length">
                    Show <span x-text="hiddenCount"></span> more items
                </flux:button>
            </div>
        </flux:card>

        {{-- Today's Food Diary --}}
        <flux:card class="!rounded-2xl lg:sticky lg:top-6 lg:col-span-2">
            <div class="mb-4 flex items-center gap-2">
                <flux:icon.book-open class="size-5 text-emerald-500" aria-hidden="true" />
                <flux:heading size="lg" level="2">Today's Food Diary</flux:heading>
            </div>

            @if($this->todayConsumed->isEmpty())
                <x-ui.empty-state icon="sparkles" title="Nothing logged today yet." description="Add your first meal from the catalogue to start filling your rings." />
            @else
                <ul class="space-y-2">
                    @foreach($this->todayConsumed as $item)
                        <li class="rounded-xl bg-zinc-50 p-3 dark:bg-white/5" wire:key="consumed-{{ $loop->index }}-{{ $item->name }}">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 truncate text-sm font-medium" title="{{ $item->name }}">{{ $item->name }}</p>
                                <flux:badge size="sm" color="zinc" class="shrink-0">× {{ $item->quantity }}</flux:badge>
                            </div>
                            <p class="mt-1 flex flex-wrap gap-x-3 text-xs tabular-nums text-zinc-600 dark:text-zinc-400">
                                <span>P {{ round($item->protein) }}g</span>
                                <span>C {{ round($item->carbs) }}g</span>
                                <span>F {{ round($item->fat) }}g</span>
                                <span class="font-semibold text-zinc-900 dark:text-white">{{ round($item->calories) }} kcal</span>
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 rounded-xl bg-linear-to-br from-emerald-700 to-cyan-800 p-4 text-white">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/80">Daily total</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">{{ round($totals->calories) }} kcal</p>
                    <p class="mt-1 flex flex-wrap gap-x-3 text-sm tabular-nums text-white/90">
                        <span>P {{ round($totals->protein) }}g</span>
                        <span>C {{ round($totals->carbs) }}g</span>
                        <span>F {{ round($totals->fat) }}g</span>
                    </p>
                </div>
            @endif
        </flux:card>
    </div>

    {{-- Add to Catalogue --}}
    <flux:modal wire:model="showAddItemForm" class="w-full max-w-lg">
        <flux:heading size="lg" class="mb-1">Add to Catalogue</flux:heading>
        <flux:subheading class="mb-5">Create a food once and quick-add it any day.</flux:subheading>

        @if($itemAddedSuccess)
            <x-ui.success-banner class="mb-4" wire:key="item-added-{{ $this->foodItems->count() }}">Food item added!</x-ui.success-banner>
        @endif

        <form wire:submit="addMealItem" class="space-y-4">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="newItemName" placeholder="e.g. Chicken breast (100g)" />
                <flux:error name="newItemName" />
            </flux:field>

            <div class="grid grid-cols-3 gap-3">
                <flux:field>
                    <flux:label>Carbs (g)</flux:label>
                    <flux:input wire:model.live.debounce.300ms="newItemCarbs" type="number" min="0" step="0.1" />
                    <flux:error name="newItemCarbs" />
                </flux:field>
                <flux:field>
                    <flux:label>Protein (g)</flux:label>
                    <flux:input wire:model.live.debounce.300ms="newItemProtein" type="number" min="0" step="0.1" />
                    <flux:error name="newItemProtein" />
                </flux:field>
                <flux:field>
                    <flux:label>Fat (g)</flux:label>
                    <flux:input wire:model.live.debounce.300ms="newItemFat" type="number" min="0" step="0.1" />
                    <flux:error name="newItemFat" />
                </flux:field>
            </div>

            <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 dark:bg-emerald-500/10">
                <div>
                    <p class="text-sm font-medium text-emerald-900 dark:text-emerald-100">Calories (auto-calculated)</p>
                    <p class="text-xs text-emerald-800/80 dark:text-emerald-200/80">Calculated as protein × 4 + carbs × 4 + fat × 9 kcal/g</p>
                </div>
                <p class="text-2xl font-bold tabular-nums text-emerald-800 dark:text-emerald-200" aria-live="polite">{{ $this->calculatedCalories }}</p>
            </div>

            <div class="flex gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" class="flex-1">Close</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="plus" class="flex-1">Add to Catalogue</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
