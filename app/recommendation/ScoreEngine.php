<?php

class ScoreEngine
{
    public static function calculate(array $behaviorData, array $trendData): array
    {
        $score = 0;
        $reasons = [];

        $completionRate30 = (float) ($behaviorData['completion_rate_30'] ?? 0);
        $currentStreak = (int) ($behaviorData['current_streak'] ?? 0);
        $consecutiveFailures = (int) ($behaviorData['consecutive_failures'] ?? 0);
        $trend = $trendData['trend'] ?? 'neutral';

        if ($completionRate30 > 80) {
            $score += 2;
            $reasons[] = 'Taxa de conclusão acima de 80%';
        } elseif ($completionRate30 < 40) {
            $score -= 2;
            $reasons[] = 'Taxa de conclusão abaixo de 40%';
        }

        if ($currentStreak > 7) {
            $score += 2;
            $reasons[] = 'Streak maior que 7 dias';
        }

        if ($trend === 'positive') {
            $score += 1;
            $reasons[] = 'Tendência positiva recente';
        } elseif ($trend === 'negative') {
            $score -= 1;
            $reasons[] = 'Tendência negativa recente';
        }

        if ($consecutiveFailures >= 3) {
            $score -= 3;
            $reasons[] = 'Falhas consecutivas por 3 dias ou mais';
        }

        $risk = self::classifyRisk($score, $completionRate30, $consecutiveFailures, $trend);

        return [
            'score' => $score,
            'reasons' => $reasons,
            'risk_level' => $risk
        ];
    }

    private static function classifyRisk(int $score, float $completionRate30, int $consecutiveFailures, string $trend): string
    {
        if ($consecutiveFailures >= 3 || ($trend === 'negative' && $completionRate30 < 50)) {
            return 'at_risk';
        }

        if ($score >= 4) {
            return 'high_performer';
        }

        if ($score >= 1) {
            return 'stable';
        }

        if ($score >= -1) {
            return 'attention';
        }

        return 'at_risk';
    }
}
