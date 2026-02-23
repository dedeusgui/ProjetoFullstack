<?php

namespace App\Support;

class TimeOfDayMapper
{
    public static function toDatabase(string $timePt): string
    {
        $map = [
            'Manhã' => 'morning',
            'Tarde' => 'afternoon',
            'Noite' => 'evening',
        ];

        return $map[$timePt] ?? 'anytime';
    }

    public static function toDisplay(string $timeEn): string
    {
        $map = [
            'morning' => 'Manhã',
            'afternoon' => 'Tarde',
            'evening' => 'Noite',
            'anytime' => 'Qualquer',
        ];

        return $map[$timeEn] ?? 'Qualquer';
    }
}
