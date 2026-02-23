<?php

namespace App\Api\Internal;

use App\Habits\HabitSchedulePolicy;
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
            $todayHabitsRaw = $habitQueryService->getTodayHabits($userId, $habitQueryService->getUserTodayDate($userId));
            $todayHabits = array_map($mapHabit, $todayHabitsRaw);

            $response['data'] = [
                'count' => count($todayHabits),
                'habits' => $todayHabits
            ];

            return $response;
        }

        if ($scope === 'page') {
            $todayDate = $habitQueryService->getUserTodayDate($userId);
            $allActiveHabitsRaw = $habitQueryService->getUserHabits($userId);
            $todayHabitsRaw = $habitQueryService->getTodayHabits($userId, $todayDate);
            $archivedHabitsRaw = $habitQueryService->getArchivedHabits($userId);

            $todayHabits = array_map(static function (array $habit) use ($todayDate): array {
                $completedToday = (bool) ($habit['completed_today'] ?? false);
                $nextBaseDate = $completedToday
                    ? date('Y-m-d', strtotime($todayDate . ' +1 day'))
                    : $todayDate;

                return [
                    'id' => (int) $habit['id'],
                    'name' => $habit['title'],
                    'description' => $habit['description'] ?? '',
                    'category' => $habit['category_name'] ?? 'Sem categoria',
                    'time' => mapTimeOfDayReverse($habit['time_of_day'] ?? 'anytime'),
                    'color' => $habit['color'] ?? '#4a74ff',
                    'streak' => (int) ($habit['current_streak'] ?? 0),
                    'completed_today' => $completedToday,
                    'created_at' => $habit['created_at'] ?? null,
                    'frequency' => $habit['frequency'] ?? 'daily',
                    'target_days' => HabitSchedulePolicy::normalizeTargetDays($habit['target_days'] ?? null),
                    'goal_type' => $habit['goal_type'] ?? 'completion',
                    'goal_value' => (int) ($habit['goal_value'] ?? 1),
                    'goal_unit' => $habit['goal_unit'] ?? '',
                    'can_complete_today' => HabitSchedulePolicy::isScheduledForDate($habit, $todayDate) && !$completedToday,
                    'next_due_date' => HabitSchedulePolicy::getNextDueDate($habit, $nextBaseDate, $todayDate),
                ];
            }, $todayHabitsRaw);

            $archivedHabits = array_map(static function (array $habit): array {
                return [
                    'id' => (int) $habit['id'],
                    'name' => $habit['title'],
                    'category' => $habit['category_name'] ?? 'Sem categoria',
                    'archived_at' => $habit['archived_at'] ?? null,
                ];
            }, $archivedHabitsRaw);

            $weekDaysMeta = [
                1 => 'Segunda',
                2 => 'Terça',
                3 => 'Quarta',
                4 => 'Quinta',
                5 => 'Sexta',
                6 => 'Sábado',
                0 => 'Domingo',
            ];

            $habitsByWeekDay = [];
            foreach ($weekDaysMeta as $weekDayIndex => $weekDayLabel) {
                $habitsByWeekDay[$weekDayIndex] = [
                    'label' => $weekDayLabel,
                    'habits' => [],
                ];
            }

            foreach ($allActiveHabitsRaw as $habitRaw) {
                $groupHabit = [
                    'id' => (int) $habitRaw['id'],
                    'name' => $habitRaw['title'],
                    'frequency' => $habitRaw['frequency'] ?? 'daily',
                    'target_days' => HabitSchedulePolicy::normalizeTargetDays($habitRaw['target_days'] ?? null),
                ];

                if ($groupHabit['frequency'] === 'daily') {
                    foreach (array_keys($habitsByWeekDay) as $weekDayIndex) {
                        $habitsByWeekDay[$weekDayIndex]['habits'][] = $groupHabit;
                    }
                    continue;
                }

                foreach ($groupHabit['target_days'] as $weekDayIndex) {
                    if (isset($habitsByWeekDay[$weekDayIndex])) {
                        $habitsByWeekDay[$weekDayIndex]['habits'][] = $groupHabit;
                    }
                }
            }

            $response['data'] = [
                'today_date' => $todayDate,
                'stats' => [
                    'total_habits' => $habitQueryService->getTotalHabits($userId),
                    'active_habits' => $habitQueryService->getTotalHabits($userId),
                    'archived_habits' => $habitQueryService->getArchivedHabitsCount($userId),
                ],
                'habits' => $todayHabits,
                'archived_habits' => $archivedHabits,
                'habits_by_week_day' => $habitsByWeekDay,
                'categories' => $habitQueryService->getAllCategories(),
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
