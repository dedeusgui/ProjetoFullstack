<?php

class HabitInputSanitizer
{
    public static function fromRequest(array $request): array
    {
        $title = trim($request['title'] ?? $request['name'] ?? '');
        $description = trim($request['description'] ?? '');
        $category = trim($request['category'] ?? '');
        $timeOfDay = trim($request['time'] ?? $request['time_of_day'] ?? '');
        $color = trim($request['color'] ?? '#4a74ff');
        $icon = trim($request['icon'] ?? '');
        $frequency = trim($request['frequency'] ?? 'daily');
        $goalType = trim($request['goal_type'] ?? 'completion');
        $goalValue = max(1, (int) ($request['goal_value'] ?? 1));
        $goalUnit = trim($request['goal_unit'] ?? '');

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#4a74ff';
        }

        if (!in_array($frequency, ['daily', 'weekly', 'custom'], true)) {
            $frequency = 'daily';
        }

        if (!in_array($goalType, ['completion', 'quantity', 'duration'], true)) {
            $goalType = 'completion';
        }

        $targetDaysValues = [];
        $targetDays = $request['target_days'] ?? [];
        if (is_array($targetDays)) {
            foreach ($targetDays as $day) {
                $dayInt = (int) $day;
                if ($dayInt >= 0 && $dayInt <= 6) {
                    $targetDaysValues[] = $dayInt;
                }
            }
            $targetDaysValues = array_values(array_unique($targetDaysValues));
        }

        $errors = [];
        if ($title === '') {
            $errors[] = 'O título do hábito é obrigatório.';
        }

        if ($category === '') {
            $errors[] = 'A categoria é obrigatória.';
        }

        if ($timeOfDay === '') {
            $errors[] = 'O período do dia é obrigatório.';
        }

        if (($frequency === 'weekly' || $frequency === 'custom') && count($targetDaysValues) === 0) {
            $errors[] = 'Selecione pelo menos um dia da semana para frequência semanal/customizada.';
        }

        return [
            'data' => [
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'time_of_day' => $timeOfDay,
                'color' => $color,
                'icon' => $icon,
                'frequency' => $frequency,
                'target_days_json' => count($targetDaysValues) > 0 ? json_encode($targetDaysValues) : null,
                'goal_type' => $goalType,
                'goal_value' => $goalValue,
                'goal_unit' => $goalUnit
            ],
            'errors' => $errors
        ];
    }
}
