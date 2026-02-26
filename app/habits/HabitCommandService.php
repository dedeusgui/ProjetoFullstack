<?php

namespace App\Habits;

use App\Repository\CategoryRepository;
use App\Repository\HabitRepository;
use App\Support\TimeOfDayMapper;

class HabitCommandService
{
    private HabitRepository $habitRepository;
    private CategoryRepository $categoryRepository;

    public function __construct(\mysqli $conn)
    {
        $this->habitRepository = new HabitRepository($conn);
        $this->categoryRepository = new CategoryRepository($conn);
    }

    public function create(int $userId, array $request): array
    {
        $input = HabitInputSanitizer::fromRequest($request);
        if (!empty($input['errors'])) {
            return ['success' => false, 'message' => $input['errors'][0]];
        }

        $data = $this->prepareHabitData($input['data']);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        if (!$this->habitRepository->createForUser($userId, (int) $data['category_id'], $data)) {
            return ['success' => false, 'message' => 'Erro ao criar hábito. Tente novamente.'];
        }

        return ['success' => true, 'message' => 'Hábito criado com sucesso!'];
    }

    public function update(int $userId, int $habitId, array $request): array
    {
        if ($habitId <= 0) {
            return ['success' => false, 'message' => 'Hábito inválido.'];
        }

        if (!$this->habitRepository->userOwnsHabit($habitId, $userId)) {
            return ['success' => false, 'message' => 'Você não tem permissão para editar este hábito.'];
        }

        $input = HabitInputSanitizer::fromRequest($request);
        if (!empty($input['errors'])) {
            return ['success' => false, 'message' => $input['errors'][0]];
        }

        $data = $this->prepareHabitData($input['data']);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        if (!$this->habitRepository->updateForUser($habitId, $userId, (int) $data['category_id'], $data)) {
            return ['success' => false, 'message' => 'Erro ao atualizar hábito. Tente novamente.'];
        }

        return ['success' => true, 'message' => 'Hábito atualizado com sucesso!'];
    }

    public function delete(int $userId, int $habitId): array
    {
        if ($habitId <= 0) {
            return ['success' => false, 'message' => 'Hábito inválido.'];
        }

        if (!$this->habitRepository->userOwnsHabit($habitId, $userId)) {
            return ['success' => false, 'message' => 'Você não tem permissão para deletar este hábito.'];
        }

        if (!$this->habitRepository->deleteForUser($habitId, $userId)) {
            return ['success' => false, 'message' => 'Erro ao deletar hábito. Tente novamente.'];
        }

        return ['success' => true, 'message' => 'Hábito deletado com sucesso!'];
    }

    public function archive(int $userId, int $habitId): array
    {
        if ($habitId <= 0) {
            return ['success' => false, 'message' => 'Hábito inválido.'];
        }

        if (!$this->habitRepository->userOwnsHabit($habitId, $userId)) {
            return ['success' => false, 'message' => 'Você não tem permissão para modificar este hábito.'];
        }

        if (!$this->habitRepository->archiveForUser($habitId, $userId)) {
            return ['success' => false, 'message' => 'Erro ao arquivar hábito. Tente novamente.'];
        }

        return ['success' => true, 'message' => 'Hábito arquivado com sucesso!'];
    }

    public function restore(int $userId, int $habitId): array
    {
        if ($habitId <= 0) {
            return ['success' => false, 'message' => 'Hábito inválido.'];
        }

        if (!$this->habitRepository->userOwnsHabit($habitId, $userId)) {
            return ['success' => false, 'message' => 'Você não tem permissão para modificar este hábito.'];
        }

        if (!$this->habitRepository->restoreForUser($habitId, $userId)) {
            return ['success' => false, 'message' => 'Erro ao restaurar hábito. Tente novamente.'];
        }

        return ['success' => true, 'message' => 'Hábito restaurado com sucesso!'];
    }

    private function prepareHabitData(array $data): array
    {
        $categoryId = $this->categoryRepository->findIdByName((string) ($data['category'] ?? ''));
        if (!$categoryId) {
            return ['error' => 'Categoria inválida.'];
        }

        $data['category_id'] = $categoryId;
        $data['time_of_day_db'] = TimeOfDayMapper::toDatabase((string) ($data['time_of_day'] ?? ''));

        return $data;
    }
}
