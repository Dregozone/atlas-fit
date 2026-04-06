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

    // Add new meal item form
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
        return MealItem::where('is_active', true)->orderBy('name')->get();
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
                'green' => 'text-green-600 dark:text-green-500 font-semibold',
                'amber' => 'text-amber-500 dark:text-amber-400 font-semibold',
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
    }

    public function quickAdd(int $itemId): void
    {
        $item = MealItem::findOrFail($itemId, ['id', 'name']);

        Consumed::create([
            'user_id'      => auth()->id(),
            'meal_item_id' => $itemId,
            'quantity'     => 1,
        ]);

        $this->quickAddSuccess = true;
        $this->quickAddName = $item->name;
        unset($this->todayConsumed, $this->todayTotals, $this->remainingMacros, $this->catalogData);
    }
};
?>

    <div class="flex flex-col gap-6 p-6">

        <div>
            <flux:heading size="xl">Nutrition</flux:heading>
            <flux:text class="text-zinc-500">Track your daily food intake and hit your macro targets.</flux:text>
        </div>

        {{-- Macro Goals Summary --}}
        @if($this->macroGoals['calories'])
            <flux:card>
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="lg">Daily Targets — {{ $this->macroGoals['goal'] }}</flux:heading>
                    <flux:text class="text-sm text-zinc-400">Based on your profile settings</flux:text>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach(['protein' => 'Protein', 'carbs' => 'Carbs', 'fat' => 'Fat', 'calories' => 'Calories'] as $key => $label)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <flux:text class="text-sm text-zinc-500">{{ $label }}</flux:text>
                            <div class="flex items-baseline gap-1">
                                <span class="text-xl font-bold">{{ round($this->todayTotals->$key) }}</span>
                                <span class="text-sm text-zinc-400">/ {{ $this->macroGoals[$key] }}{{ $key === 'calories' ? 'kcal' : 'g' }}</span>
                            </div>
                            @php $remaining = $this->macroGoals[$key] - $this->todayTotals->$key; @endphp
                            @php $percent = $this->macroGoals[$key] > 0 ? min(100, round(($this->todayTotals->$key / $this->macroGoals[$key]) * 100)) : 0; @endphp
                            <div class="mt-2 h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-2 rounded-full {{ $percent >= 100 ? 'bg-red-500' : 'bg-blue-500' }} transition-all" style="width: {{ $percent }}%"></div>
                            </div>
                            <flux:text class="mt-1 text-xs text-zinc-400">
                                {{ $remaining > 0 ? round($remaining) . ' remaining' : abs(round($remaining)) . ' over' }}
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        @else
            <flux:callout icon="information-circle">
                <flux:callout.text>
                    Set your body weight and fitness goal in <flux:link href="{{ route('profile.edit') }}" wire:navigate>Profile settings</flux:link> to see personalised macro targets.
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Add to Catalogue --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Add to Catalogue</flux:heading>

                @if($itemAddedSuccess)
                    <flux:callout icon="check-circle" color="green" class="mb-4">
                        <flux:callout.text>Food item added!</flux:callout.text>
                    </flux:callout>
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
                            <flux:input wire:model.live="newItemCarbs" type="number" min="0" step="0.1" />
                            <flux:error name="newItemCarbs" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Protein (g)</flux:label>
                            <flux:input wire:model.live="newItemProtein" type="number" min="0" step="0.1" />
                            <flux:error name="newItemProtein" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Fat (g)</flux:label>
                            <flux:input wire:model.live="newItemFat" type="number" min="0" step="0.1" />
                            <flux:error name="newItemFat" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Calories (auto-calculated)</flux:label>
                        <flux:input type="number" value="{{ $this->calculatedCalories }}" readonly />
                        <flux:description>Calculated as protein × 4 + carbs × 4 + fat × 9 kcal/g</flux:description>
                    </flux:field>

                    <flux:button type="submit" variant="primary" class="w-full">Add to Catalogue</flux:button>
                </form>
            </flux:card>

            {{-- Today's Food Diary --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Today's Food Diary</flux:heading>

                @if($this->todayConsumed->isEmpty())
                    <flux:text class="text-zinc-500">Nothing logged today yet.</flux:text>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Item</flux:table.column>
                            <flux:table.column>Qty</flux:table.column>
                            <flux:table.column>P</flux:table.column>
                            <flux:table.column>C</flux:table.column>
                            <flux:table.column>F</flux:table.column>
                            <flux:table.column>kcal</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->todayConsumed as $item)
                                <flux:table.row>
                                    <flux:table.cell class="font-medium">{{ $item->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $item->quantity }}</flux:table.cell>
                                    <flux:table.cell>{{ round($item->protein) }}g</flux:table.cell>
                                    <flux:table.cell>{{ round($item->carbs) }}g</flux:table.cell>
                                    <flux:table.cell>{{ round($item->fat) }}g</flux:table.cell>
                                    <flux:table.cell>{{ round($item->calories) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                            <flux:table.row class="font-semibold border-t-2 dark:border-zinc-600">
                                <flux:table.cell>Total</flux:table.cell>
                                <flux:table.cell>—</flux:table.cell>
                                <flux:table.cell>{{ round($this->todayTotals->protein) }}g</flux:table.cell>
                                <flux:table.cell>{{ round($this->todayTotals->carbs) }}g</flux:table.cell>
                                <flux:table.cell>{{ round($this->todayTotals->fat) }}g</flux:table.cell>
                                <flux:table.cell>{{ round($this->todayTotals->calories) }}</flux:table.cell>
                            </flux:table.row>
                        </flux:table.rows>
                    </flux:table>
                @endif
            </flux:card>

        </div>

        {{-- Food Catalogue --}}
        <flux:card x-data="{
            search: '',
            names: {!! \Illuminate\Support\Js::from($this->catalogData->pluck('name')->map(fn($n) => strtolower($n))->values()) !!},
            limit: 10,
            matches(name) {
                const q = this.search.toLowerCase();
                return !q || name.includes(q);
            },
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
            }
        }">
            <flux:heading size="lg" class="mb-4">Food Catalogue</flux:heading>

            @if($quickAddSuccess)
                <flux:callout icon="check-circle" color="green" class="mb-4">
                    <flux:callout.text>1 serving of <strong>{{ $quickAddName }}</strong> added to today's diary!</flux:callout.text>
                </flux:callout>
            @endif

            <flux:input x-model="search" clearable placeholder="Start typing a food or drink..." class="mb-4" />

            @if($this->macroGoals['calories'])
                <flux:text class="mb-3 text-xs text-zinc-500">
                    Colours show how close to your daily target eating this item would take you:
                    <span class="text-green-600 dark:text-green-500 font-semibold">■ Green</span> = projected total stays under 80% of daily goal,
                    <span class="text-amber-500 dark:text-amber-400 font-semibold">■ Amber</span> = would push past 80%,
                    <span class="text-red-600 dark:text-red-400 font-semibold">■ Red</span> = would exceed the daily goal.
                    Items are ordered with the best overall fit at the top.
                </flux:text>
            @endif

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Protein</flux:table.column>
                    <flux:table.column>Carbs</flux:table.column>
                    <flux:table.column>Fat</flux:table.column>
                    <flux:table.column>Calories</flux:table.column>
                    <flux:table.column>Quick Add</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->catalogData as $item)
                        <flux:table.row wire:key="catalog-{{ $item->id }}" x-show="isVisible({{ $loop->index }})">
                            <flux:table.cell class="font-medium">{{ $item->name }}</flux:table.cell>
                            <flux:table.cell><span class="{{ $item->proteinClass }}">{{ $item->protein }}g</span></flux:table.cell>
                            <flux:table.cell><span class="{{ $item->carbsClass }}">{{ $item->carbs }}g</span></flux:table.cell>
                            <flux:table.cell><span class="{{ $item->fatClass }}">{{ $item->fat }}g</span></flux:table.cell>
                            <flux:table.cell><span class="{{ $item->caloriesClass }}">{{ $item->calories }} kcal</span></flux:table.cell>
                            <flux:table.cell>
                                <flux:button wire:click="quickAdd({{ $item->id }})" size="sm" variant="ghost" icon="plus-circle">
                                    Add
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                    <flux:table.row x-show="!hasResults">
                        <flux:table.cell colspan="6" class="text-center text-zinc-500">No food items match your search.</flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>

            <div x-show="!search && hiddenCount > 0" class="mt-3 text-center">
                <flux:button variant="ghost" size="sm" x-on:click="limit = names.length">
                    Show <span x-text="hiddenCount"></span> more items
                </flux:button>
            </div>
        </flux:card>

    </div>
