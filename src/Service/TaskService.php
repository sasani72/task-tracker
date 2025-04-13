<?php

namespace App\Service;

use App\DTO\TaskDTO;
use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Repository\TaskRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\InvalidStatusTransitionException;
use App\Exception\TaskNotFoundException;

final class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function createTask(TaskDTO $taskDTO): Task
    {
        $errors = $this->validator->validate($taskDTO);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $task = new Task(
            $taskDTO->title,
            $taskDTO->description,
            $taskDTO->assigneeId
        );

        $this->taskRepository->save($task);
        return $task;
    }

    public function getTask(Uuid $id): ?Task
    {
        return $this->taskRepository->find($id);
    }

    public function getAllTasks(): array
    {
        return $this->taskRepository->findAll();
    }

    public function getTasksByStatus(TaskStatus $status): array
    {
        return $this->taskRepository->findByStatus($status);
    }

    public function getTasksByAssignee(string $assigneeId): array
    {
        return $this->taskRepository->findByAssignee($assigneeId);
    }

    public function updateTaskStatus(Uuid $id, TaskStatus $status): Task
    {
        $task = $this->taskRepository->find($id);
        if (!$task) {
            throw new TaskNotFoundException($id);
        }

        if (!$this->isValidStatusTransition($task->getStatus(), $status)) {
            throw new InvalidStatusTransitionException($task->getStatus(), $status);
        }

        $updatedTask = $task->withStatus($status);
        $this->taskRepository->update($updatedTask);
        return $updatedTask;
    }

    private function isValidStatusTransition(TaskStatus $current, TaskStatus $new): bool
    {
        $allowedTransitions = [
            TaskStatus::TODO->value => [TaskStatus::IN_PROGRESS],
            TaskStatus::IN_PROGRESS->value => [TaskStatus::TODO, TaskStatus::DONE],
            TaskStatus::DONE->value => [TaskStatus::IN_PROGRESS]
        ];

        return in_array($new, $allowedTransitions[$current->value]);
    }

    public function assignTask(Uuid $id, string $assigneeId): Task
    {
        $task = $this->taskRepository->find($id);
        if (!$task) {
            throw new TaskNotFoundException($id);
        }

        $updatedTask = $task->withAssignee($assigneeId);
        $this->taskRepository->update($updatedTask);
        return $updatedTask;
    }
} 