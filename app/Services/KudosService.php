<?php

namespace App\Services;

class KudosService
{
    /**
     * Calculate kudos for a question answer
     */
    public static function calculateKudos($user, $question, $correct, $timeTaken = null, $streakCount = 0)
    {
        $config = LiveService::getConfig($user); // Changed from PartnerConfigService
        $kudosConfig = $config['kudos'];
        
        if (!$correct) {
            return $kudosConfig['incorrect_consolation'] ?? 0;
        }
        
        // Base kudos for correct answer
        $baseKudos = $kudosConfig['correct_base'] ?? 1;
        $difficultyMultiplier = $kudosConfig['correct_difficulty_multiplier'] ?? 1;
        
        // Calculate base points with difficulty
        $kudos = $baseKudos + ($question->difficulty_id * $difficultyMultiplier);
        
        // Apply streak bonus
        if (($kudosConfig['streak_bonus_enabled'] ?? false) && $streakCount > 1) {
            $streakMultiplier = $kudosConfig['streak_bonus_multiplier'] ?? 0.1;
            $kudos += $kudos * ($streakCount - 1) * $streakMultiplier;
        }
        
        // Apply time bonus
        if (($kudosConfig['time_bonus_enabled'] ?? false) && $timeTaken) {
            $threshold = $kudosConfig['time_bonus_threshold'] ?? 60;
            if ($timeTaken <= $threshold) {
                $timeMultiplier = $kudosConfig['time_bonus_multiplier'] ?? 0.2;
                $kudos += $kudos * $timeMultiplier;
            }
        }
        
        return round($kudos, 2);
    }
    
    /**
     * Get user's current streak count for a specific test
     */
    public static function getStreakCount($user, $testId = null)
    {
        if (!$testId) {
            return 0;
        }
        
        // Get most recent answers for this test
        $recentAnswers = $user->myQuestions()
            ->wherePivot('test_id', $testId)
            ->wherePivot('question_answered', true)
            ->orderBy('pivot_answered_date', 'desc')
            ->limit(10)
            ->get()
            ->pluck('pivot.correct');
            
        $streak = 0;
        foreach ($recentAnswers as $correct) {
            if ($correct) {
                $streak++;
            } else {
                break; // Streak broken
            }
        }
        
        return $streak;
    }
    
    /**
     * Get kudos configuration for user's partner type
     */
    public static function getKudosConfig($user)
    {
        $config = LiveService::getConfig($user); // Changed from PartnerConfigService
        return $config['kudos'] ?? [];
    }
    
    /**
     * Check if streak bonuses are enabled for user
     */
    public static function hasStreakBonuses($user)
    {
        $config = self::getKudosConfig($user);
        return $config['streak_bonus_enabled'] ?? false;
    }
    
    /**
     * Check if time bonuses are enabled for user
     */
    public static function hasTimeBonuses($user)
    {
        $config = self::getKudosConfig($user);
        return $config['time_bonus_enabled'] ?? false;
    }
    
    /**
     * Get consolation kudos for incorrect answers
     */
    public static function getConsolationKudos($user)
    {
        $config = self::getKudosConfig($user);
        return $config['incorrect_consolation'] ?? 0;
    }
    
    /**
     * Calculate potential kudos (for preview/motivation)
     */
    public static function calculatePotentialKudos($user, $question, $streakCount = 0)
    {
        $config = LiveService::getConfig($user); // Changed from PartnerConfigService
        $kudosConfig = $config['kudos'];
        
        $baseKudos = $kudosConfig['correct_base'] ?? 1;
        $difficultyMultiplier = $kudosConfig['correct_difficulty_multiplier'] ?? 1;
        
        $kudos = $baseKudos + ($question->difficulty_id * $difficultyMultiplier);
        
        // Show potential streak bonus
        if (($kudosConfig['streak_bonus_enabled'] ?? false) && $streakCount > 0) {
            $streakMultiplier = $kudosConfig['streak_bonus_multiplier'] ?? 0.1;
            $kudos += $kudos * $streakCount * $streakMultiplier;
        }
        
        // Show potential time bonus (assume user answers quickly)
        if ($kudosConfig['time_bonus_enabled'] ?? false) {
            $timeMultiplier = $kudosConfig['time_bonus_multiplier'] ?? 0.2;
            $timeBonus = $kudos * $timeMultiplier;
            return [
                'base' => round($kudos, 2),
                'with_time_bonus' => round($kudos + $timeBonus, 2),
                'time_threshold' => $kudosConfig['time_bonus_threshold'] ?? 60
            ];
        }
        
        return [
            'base' => round($kudos, 2),
            'with_time_bonus' => round($kudos, 2),
            'time_threshold' => null
        ];
    }
    
    /**
     * Get kudos breakdown for display purposes
     */
    public static function getKudosBreakdown($user, $question, $correct, $timeTaken = null, $streakCount = 0)
    {
        $config = LiveService::getConfig($user); // Changed from PartnerConfigService
        $kudosConfig = $config['kudos'];
        
        if (!$correct) {
            return [
                'total' => $kudosConfig['incorrect_consolation'] ?? 0,
                'breakdown' => [
                    'consolation' => $kudosConfig['incorrect_consolation'] ?? 0
                ]
            ];
        }
        
        $baseKudos = $kudosConfig['correct_base'] ?? 1;
        $difficultyMultiplier = $kudosConfig['correct_difficulty_multiplier'] ?? 1;
        $baseWithDifficulty = $baseKudos + ($question->difficulty_id * $difficultyMultiplier);
        
        $breakdown = [
            'base' => $baseKudos,
            'difficulty_bonus' => $question->difficulty_id * $difficultyMultiplier,
            'streak_bonus' => 0,
            'time_bonus' => 0
        ];
        
        $total = $baseWithDifficulty;
        
        // Calculate streak bonus
        if (($kudosConfig['streak_bonus_enabled'] ?? false) && $streakCount > 1) {
            $streakMultiplier = $kudosConfig['streak_bonus_multiplier'] ?? 0.1;
            $streakBonus = $baseWithDifficulty * ($streakCount - 1) * $streakMultiplier;
            $breakdown['streak_bonus'] = round($streakBonus, 2);
            $total += $streakBonus;
        }
        
        // Calculate time bonus
        if (($kudosConfig['time_bonus_enabled'] ?? false) && $timeTaken) {
            $threshold = $kudosConfig['time_bonus_threshold'] ?? 60;
            if ($timeTaken <= $threshold) {
                $timeMultiplier = $kudosConfig['time_bonus_multiplier'] ?? 0.2;
                $timeBonus = $baseWithDifficulty * $timeMultiplier;
                $breakdown['time_bonus'] = round($timeBonus, 2);
                $total += $timeBonus;
            }
        }
        
        return [
            'total' => round($total, 2),
            'breakdown' => $breakdown
        ];
    }
}