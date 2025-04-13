<?php

namespace App\Repository;

use App\Entity\Task;
use App\Enum\TaskStatus;
use Symfony\Component\Uid\Uuid;

interface TaskRepositoryInterface
{
    public function save(Task $task): void;
    
    public function find(Uuid $id): ?Task;
    
    public function findAll(): array;
    
    public function findByStatus(TaskStatus $status): array;
    
    public function findByAssignee(string $assigneeId): array;
    
    public function delete(Task $task): void;

    public function update(Task $task): void;
} 