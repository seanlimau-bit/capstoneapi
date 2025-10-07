<?php

namespace App\Support;

class McqNotaPolicy
{
    // Tune these two to change global NOTA behavior
    public const NOTA_INCLUDE_RATE = 0.20;  // 20% of MCQs include NOTA
    public const NOTA_CORRECT_WHEN_PRESENT_RATE = 0.50; // 50% of the NOTA-included items have NOTA correct

    public static function decide(): array
    {
        $include = mt_rand() / mt_getrandmax() < self::NOTA_INCLUDE_RATE;
        $notaIsCorrect = false;
        if ($include) {
            $notaIsCorrect = mt_rand() / mt_getrandmax() < self::NOTA_CORRECT_WHEN_PRESENT_RATE;
        }
        return [$include, $notaIsCorrect];
    }
}
