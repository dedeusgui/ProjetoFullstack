<?php

namespace App\Api\Internal;

use App\Habits\HabitQueryService;

class HabitsApiPayloadBuilder
{
    public static function build(\mysqli $conn, int $userId, string $scope = 'all'): array
    {
        $habitQueryService = new HabitQueryService($conn);
        $mapHabit = static function (array $habit): array {
            return [
                'id' => (int) $habit['id'],
                'name' => $habit['title'],
                'description' => $habit['description'] ?? '',
                'category' => $habit['category_name'] ?? 'Sem categoria',
                'time' => mapTimeOfDayReverse($habit['time_of_day'] ?? 'anytime'),
                'color' => $habit['color'] ?? '#4a74ff',
                'streak' => (int) ($habit['current_streak'] ?? 0),
                'completed_today' => (bool) ($habit['completed_today'] ?? false),
                'created_at' => $habit['created_at'] ?? null,
                'frequency' => $habit['frequency'] ?? 'daily',
                'goal_type' => $habit['goal_type'] ?? 'completion',
                'goal_value' => (int) ($habit['goal_value'] ?? 1),
                'goal_unit' => $habit['goal_unit'] ?? '',
                'target_days' => normalizeTargetDays($habit['target_days'] ?? null)
            ];
        };

        $response = [
            'success' => true,
            'scope' => $scope,
            'generated_at' => date('c')
        ];

        if ($scope === 'today') {
            $todayHabitsRaw = $habitQueryService->getTodayHabits($userId, getUserTodayDate($conn, $userId));
            $todayHabits = array_map($mapHabit, $todayHabitsRaw);

            $response['data'] = [
                'count' => count($todayHabits),
                'habits' => $todayHabits
            ];

            return $response;
        }

        $habitsRaw = $habitQueryService->getUserHabits($userId);
        $habits = array_map($mapHabit, $habitsRaw);

        $response['data'] = [
            'count' => count($habits),
            'habits' => $habits
        ];

        return $response;
    }
}
