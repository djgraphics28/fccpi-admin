<?php

namespace App\Console\Commands;

use App\Models\Youth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RandomizeYouthColors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youth:randomize-colors
                            {--colorNumber= : Number of color groups to create}
                            {--memberIds=* : Specific member IDs to randomize (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Randomize color groups for youth members';

    /**
     * Color palette for groups
     */
    protected $colorPalette = [
        '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
        '#EC4899', '#06B6D4', '#64a00bff', '#F97316', '#6366F1'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $colorNumber = $this->option('colorNumber');
        $memberIds = $this->option('memberIds');

        // Convert memberIds to array if it's provided as a string
        if (!empty($memberIds) && is_string($memberIds)) {
            $memberIds = explode(',', $memberIds);
        }

        if (!$colorNumber) {
            $this->error('Number of color groups is required.');
            return 1;
        }

        if (!is_numeric($colorNumber) || $colorNumber < 2) {
            $this->error('Please provide a valid number of color groups (minimum 2).');
            return 1;
        }

        $colorNumber = (int) $colorNumber;

        // Validate that we don't exceed available colors
        if ($colorNumber > count($this->colorPalette)) {
            $this->error("Maximum number of colors available is " . count($this->colorPalette));
            return 1;
        }

        // Get members to process
        $query = Youth::query();
        if (!empty($memberIds)) {
            $query->whereIn('id', $memberIds);
        }

        $totalMembers = $query->count();

        if ($totalMembers === 0) {
            $this->error('No youth members found.');
            return 1;
        }

        $this->info("Total youth members to process: {$totalMembers}");
        $this->info("Number of color groups: {$colorNumber}");

        // Calculate distribution
        $distribution = $this->calculateDistribution($totalMembers, $colorNumber);

        $this->info("\nDistribution plan:");
        foreach ($distribution as $index => $count) {
            $this->line("Group " . ($index + 1) . ": {$count} members");
        }

        // Since this is called programmatically, we'll proceed without confirmation
        // Randomize and assign colors
        $this->assignColors($distribution, $colorNumber, $query);

        $this->info("\nColor randomization completed successfully!");

        // Show final distribution
        $this->showFinalDistribution($query);

        return 0;
    }

    /**
     * Calculate the distribution of members across color groups
     */
    protected function calculateDistribution(int $totalMembers, int $colorNumber): array
    {
        $baseCount = floor($totalMembers / $colorNumber);
        $remainder = $totalMembers % $colorNumber;

        $distribution = array_fill(0, $colorNumber, $baseCount);

        // Distribute remainder across groups
        for ($i = 0; $i < $remainder; $i++) {
            $distribution[$i]++;
        }

        return $distribution;
    }

    /**
     * Assign colors to youth members
     */
    protected function assignColors(array $distribution, int $colorNumber, $query): void
    {
        // Shuffle the color palette and take the required number of colors
        $selectedColors = collect($this->colorPalette)
            ->shuffle()
            ->take($colorNumber)
            ->toArray();

        // Get youth members in random order
        $youthMembers = $query->inRandomOrder()->get();

        $memberIndex = 0;

        DB::transaction(function () use ($distribution, $selectedColors, $youthMembers, &$memberIndex) {
            foreach ($distribution as $groupIndex => $groupSize) {
                $color = $selectedColors[$groupIndex];

                for ($i = 0; $i < $groupSize; $i++) {
                    if ($memberIndex < $youthMembers->count()) {
                        $youthMembers[$memberIndex]->update(['color' => $color]);
                        $memberIndex++;
                    }
                }
            }
        });
    }

    /**
     * Show the final distribution after assignment
     */
    protected function showFinalDistribution($query): void
    {
        $colorDistribution = $query->clone()
            ->select('color', DB::raw('count(*) as count'))
            ->groupBy('color')
            ->get();

        $this->info("\nFinal distribution:");
        $this->info("==================");

        foreach ($colorDistribution as $group) {
            $this->line("Color {$group->color}: {$group->count} members");
        }
    }
}
