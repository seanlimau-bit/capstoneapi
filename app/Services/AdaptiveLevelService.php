<?php

namespace App\Services;

use App\Models\Level;
use App\Models\Field;
use Illuminate\Support\Facades\Log;

class AdaptiveLevelService
{
    /**
     * Get next level up based on maxile ranges from levels table
     */
    public function getNextLevelUp(int $currentMaxile, int $fieldId): int
    {
        $nextLevel = Level::where('start_maxile_level', '>', $currentMaxile)
            ->where('status_id', 3)
            ->whereHas('tracks', function($query) use ($fieldId) {
                $query->where('field_id', $fieldId)
                      ->where('status_id', 3);
            })
            ->orderBy('start_maxile_level', 'asc')
            ->first();
        
        return $nextLevel ? $nextLevel->start_maxile_level : $currentMaxile;
    }

    /**
     * Get next level down based on maxile ranges from levels table
     */
    public function getNextLevelDown(int $currentMaxile, int $fieldId): int
    {
        $prevLevel = Level::where('end_maxile_level', '<', $currentMaxile)
            ->where('status_id', 3)
            ->whereHas('tracks', function($query) use ($fieldId) {
                $query->where('field_id', $fieldId)
                      ->where('status_id', 3);
            })
            ->orderBy('end_maxile_level', 'desc')
            ->first();
        
        return $prevLevel ? $prevLevel->start_maxile_level : $currentMaxile;
    }

    /**
     * Get the starting maxile level for a field
     */
    public function getStartingMaxile(int $fieldId): int
    {
        $startLevel = Level::where('status_id', 3)
            ->whereHas('tracks', function($query) use ($fieldId) {
                $query->where('field_id', $fieldId)
                      ->where('status_id', 3);
            })
            ->orderBy('start_maxile_level', 'asc')
            ->first();
        
        return $startLevel ? $startLevel->start_maxile_level : 100;
    }

    /**
     * Get the maximum maxile level for a field
     */
    public function getMaxLevelForField(int $fieldId): int
    {
        $maxLevel = Level::where('status_id', 3)
            ->whereHas('tracks', function($query) use ($fieldId) {
                $query->where('field_id', $fieldId)
                      ->where('status_id', 3);
            })
            ->orderBy('end_maxile_level', 'desc')
            ->first();
        
        return $maxLevel ? $maxLevel->start_maxile_level : 600;
    }

    /**
     * Get level name for display
     */
    public function getLevelDescription(int $maxile): string
    {
        $level = Level::where('start_maxile_level', '<=', $maxile)
            ->where('end_maxile_level', '>=', $maxile)
            ->first();
        
        return $level ? $level->description : 'Level ' . $maxile;
    }

    /**
     * Get all public field IDs
     */
    public function getPublicFieldIds(): array
    {
        return Field::where('status_id', 3)
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();
    }
}