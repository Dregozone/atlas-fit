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

    // Log consumed food form
    public int $meal_item_id = 0;

    #[Validate('required|exists:meal_items,id')]
    public ?int $selectedMealItemId = null;

    #[Validate('required|numeric|min:0.1|max:100')]
    public float $quantity = 1;

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

    public bool $consumedSuccess = false;
    public bool $itemAddedSuccess = false;

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

    public function logFood(): void
    {
        $this->validateOnly('selectedMealItemId');
        $this->validateOnly('quantity');

        Consumed::create([
            'user_id' => auth()->id(),
            'meal_item_id' => $this->selectedMealItemId,
            'quantity' => $this->quantity,
        ]);

        $this->reset(['selectedMealItemId', 'quantity']);
        $this->quantity = 1;
        $this->consumedSuccess = true;
        unset($this->todayConsumed, $this->todayTotals);
    }

    public function addMealItem(): void
    {
        $this->validateOnly('newItemName');
        $this->validateOnly('newItemCarbs');
        $this->validateOnly('newItemProtein');
        $this->validateOnly('newItemFat');

        $calc = new MacroCalculator;
        $calories = round($calc->calculateCalories($this->newItemCarbs, $this->newItemProtein, $this->newItemFat), 1);

        MealItem::create([
            'name' => $this->newItemName,
            'carbs' => round($this->newItemCarbs, 1),
            'protein' => round($this->newItemProtein, 1),
            'fat' => round($this->newItemFat, 1),
            'calories' => $calories,
            'is_active' => true,
        ]);

        $this->reset(['newItemName', 'newItemCarbs', 'newItemProtein', 'newItemFat']);
        $this->showAddItemForm = false;
        $this->itemAddedSuccess = true;
        unset($this->foodItems);
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

            {{-- Log Food Form --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Log Food</flux:heading>

                @if($consumedSuccess)
                    <flux:callout icon="check-circle" color="green" class="mb-4">
                        <flux:callout.text>Food logged!</flux:callout.text>
                    </flux:callout>
                @endif

                <form wire:submit="logFood" class="space-y-4">
                    <flux:field>
                        <flux:label>Food Item</flux:label>
                        <flux:select wire:model="selectedMealItemId" placeholder="Select a food...">
                            @foreach($this->foodItems as $item)
                                <flux:select.option :value="$item->id">
                                    {{ $item->name }} — P: {{ $item->protein }}g C: {{ $item->carbs }}g F: {{ $item->fat }}g
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="selectedMealItemId" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Quantity (servings)</flux:label>
                        <flux:input wire:model="quantity" type="number" min="0.1" step="0.1" max="100" />
                        <flux:error name="quantity" />
                    </flux:field>

                    <flux:button type="submit" variant="primary" class="w-full">Log Food</flux:button>
                </form>

                <flux:separator class="my-4" />

                {{-- Add New Meal Item --}}
                <div>
                    <button wire:click="$toggle('showAddItemForm')" class="flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        <flux:icon.plus class="size-4" />
                        {{ $showAddItemForm ? 'Cancel' : 'Add new food item to catalogue' }}
                    </button>

                    @if($showAddItemForm)
                        @if($itemAddedSuccess)
                            <flux:callout icon="check-circle" color="green" class="mt-3">
                                <flux:callout.text>Food item added!</flux:callout.text>
                            </flux:callout>
                        @endif
                        <form wire:submit="addMealItem" class="mt-4 space-y-4">
                            <flux:field>
                                <flux:label>Name</flux:label>
                                <flux:input wire:model="newItemName" placeholder="e.g. Chicken breast (100g)" />
                                <flux:error name="newItemName" />
                            </flux:field>

                            <div class="grid grid-cols-3 gap-3">
                                <flux:field>
                                    <flux:label>Carbs (g)</flux:label>
                                    <flux:input wire:model="newItemCarbs" type="number" min="0" step="0.1" />
                                    <flux:error name="newItemCarbs" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Protein (g)</flux:label>
                                    <flux:input wire:model="newItemProtein" type="number" min="0" step="0.1" />
                                    <flux:error name="newItemProtein" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Fat (g)</flux:label>
                                    <flux:input wire:model="newItemFat" type="number" min="0" step="0.1" />
                                    <flux:error name="newItemFat" />
                                </flux:field>
                            </div>

                            <flux:button type="submit" variant="primary" class="w-full">Add to Catalogue</flux:button>
                        </form>
                    @endif
                </div>
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
        <flux:card>
            <flux:heading size="lg" class="mb-4">Food Catalogue</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Protein</flux:table.column>
                    <flux:table.column>Carbs</flux:table.column>
                    <flux:table.column>Fat</flux:table.column>
                    <flux:table.column>Calories</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->foodItems as $item)
                        <flux:table.row>
                            <flux:table.cell class="font-medium">{{ $item->name }}</flux:table.cell>
                            <flux:table.cell>{{ $item->protein }}g</flux:table.cell>
                            <flux:table.cell>{{ $item->carbs }}g</flux:table.cell>
                            <flux:table.cell>{{ $item->fat }}g</flux:table.cell>
                            <flux:table.cell>{{ $item->calories }} kcal</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

    </div>
