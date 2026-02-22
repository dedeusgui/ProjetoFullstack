<?php

namespace App\Support;

class DateFormatter
{
    public static function formatBr(?string $date): string
    {
        if (empty($date)) {
            return 'Sem data';
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$parsed) {
            return $date;
        }

        return $parsed->format('d/m/Y');
    }
}
