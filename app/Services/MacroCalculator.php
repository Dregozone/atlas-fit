<?php

namespace App\Services;

class MacroCalculator
{
    const BULKING = 1.1;

    const MAINTAINING = 1.0;

    const CUTTING = 0.9;

    private float $weight = 0;

    private string $goal = 'Maintaining';

    private float $carbs = 0;

    private float $protein = 0;

    private float $fat = 0;

    private float $calories = 0;

    public function setWeightLbs(float $weight): void
    {
        $this->weight = $weight;
    }

    public function setGoal(string $goal): void
    {
        if (! in_array($goal, ['Bulking', 'Maintaining', 'Cutting'])) {
            throw new \InvalidArgumentException("Goal '{$goal}' is not recognised.");
        }
        $this->goal = $goal;
    }

    public function findMacros(): void
    {
        $this->protein = $this->carbs = $this->weight;

        $perc1Calories = ($this->protein * 4) / 40; // 1% of calories (40/40/20 p/c/f split)
        $this->fat = floor(($perc1Calories * 20) / 9); // 9 cal/g fat

        $modifier = match ($this->goal) {
            'Bulking' => self::BULKING,
            'Cutting' => self::CUTTING,
            default   => self::MAINTAINING,
        };

        $this->protein *= $modifier;
        $this->carbs *= $modifier;
        $this->fat *= $modifier;

        $this->calories = $this->calculateCalories($this->carbs, $this->protein, $this->fat);
    }

    public function calculateCalories(float $carbs, float $protein, float $fat): float
    {
        return ($protein * 4) + ($carbs * 4) + ($fat * 9);
    }

    public function getGoal(): string
    {
        return $this->goal;
    }

    public function getCarbs(): float
    {
        return $this->carbs;
    }

    public function getProtein(): float
    {
        return $this->protein;
    }

    public function getFat(): float
    {
        return $this->fat;
    }

    public function getCalories(): float
    {
        return $this->calories;
    }
}
