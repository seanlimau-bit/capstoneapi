<?php

return [
    'default' => [
        'lives' => [
            'enabled' => true,
            'max_lives' => 3
        ],
        'features' => [
            'show_correct_answers' => true,
            'unlimited_retakes' => false
        ],
        'kudos' => [
            'correct_base' => 1,
            'correct_difficulty_multiplier' => 1,
            'incorrect_consolation' => 0,
            'streak_bonus_enabled' => false,
            'streak_bonus_multiplier' => 0.1,
            'time_bonus_enabled' => false,
            'time_bonus_multiplier' => 0.2,
            'time_bonus_threshold' => 60
        ]
    ],
    
    'telco' => [
        'default' => [
            'lives' => ['enabled' => true, 'max_lives' => 5]
        ]
    ],
    
    'schools' => [
        'default' => [
            'lives' => ['enabled' => false]
        ]
    ]
];