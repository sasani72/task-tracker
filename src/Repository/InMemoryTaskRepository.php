<?php

namespace App\Repository;

use App\Entity\Task;
use App\Enum\TaskStatus;
use Symfony\Component\Uid\Uuid;

final class InMemoryTaskRepository implements TaskRepositoryInterface
{
    private array $tasks = [];

    /** @override */
    public function save(Task $task): void
    {
        $this->tasks[$task->getId()->toRfc4122()] = $task;
    }

    /** @override */
    public function find(Uuid $id): ?Task
    {
        return $this->tasks[$id->toRfc4122()] ?? null;
    }

    /** @override */
    public function findAll(): array
    {
        return array_values($this->tasks);
    }

    /** @override */
    public function findByStatus(TaskStatus $status): array
    {
        return array_values(array_filter(
            $this->tasks,
            fn(Task $task) => $task->getStatus() === $status
        ));
    }

    /** @override */
    public function findByAssignee(string $assigneeId): array
    {
        return array_values(array_filter(
            $this->tasks,
            fn(Task $task) => $task->getAssigneeId() === $assigneeId
        ));
    }

    /** @override */
    public function delete(Task $task): void
    {
        unset($this->tasks[$task->getId()->toRfc4122()]);
    }

    /** @override */
    public function update(Task $task): void
    {
        $this->tasks[$task->getId()->toRfc4122()] = $task;
    }
} 