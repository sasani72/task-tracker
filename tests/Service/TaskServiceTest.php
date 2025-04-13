<?php

namespace App\Tests\Service;

use App\DTO\TaskDTO;
use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Repository\InMemoryTaskRepository;
use App\Service\TaskService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validation;
use App\Exception\TaskNotFoundException;

class TaskServiceTest extends TestCase
{
    private TaskService $taskService;
    private InMemoryTaskRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryTaskRepository();
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->taskService = new TaskService($this->repository, $validator);
    }

    public function testCreateTask(): void
    {
        $taskDTO = new TaskDTO('Test Task', 'This is a test task');
        $task = $this->taskService->createTask($taskDTO);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('This is a test task', $task->getDescription());
        $this->assertEquals(TaskStatus::TODO, $task->getStatus());
        $this->assertNull($task->getAssigneeId());
    }

    public function testCreateTaskWithAssignee(): void
    {
        $taskDTO = new TaskDTO('Test Task', 'This is a test task', 'user123');
        $task = $this->taskService->createTask($taskDTO);

        $this->assertEquals('user123', $task->getAssigneeId());
    }

    public function testGetTask(): void
    {
        $taskDTO = new TaskDTO('Test Task', 'This is a test task');
        $createdTask = $this->taskService->createTask($taskDTO);

        $task = $this->taskService->getTask($createdTask->getId());

        $this->assertNotNull($task);
        $this->assertEquals($createdTask->getId(), $task->getId());
    }

    public function testGetNonExistentTask(): void
    {
        $task = $this->taskService->getTask(Uuid::v4());

        $this->assertNull($task);
    }

    public function testGetAllTasks(): void
    {
        $this->taskService->createTask(new TaskDTO('Task 1', 'Description 1'));
        $this->taskService->createTask(new TaskDTO('Task 2', 'Description 2'));

        $tasks = $this->taskService->getAllTasks();

        $this->assertCount(2, $tasks);
    }

    public function testGetTasksByStatus(): void
    {
        $task1 = $this->taskService->createTask(new TaskDTO('Task 1', 'Description 1'));
        $task2 = $this->taskService->createTask(new TaskDTO('Task 2', 'Description 2'));
        $this->taskService->updateTaskStatus($task2->getId(), TaskStatus::IN_PROGRESS);

        $todoTasks = $this->taskService->getTasksByStatus(TaskStatus::TODO);
        $inProgressTasks = $this->taskService->getTasksByStatus(TaskStatus::IN_PROGRESS);

        $this->assertCount(1, $todoTasks);
        $this->assertCount(1, $inProgressTasks);
        $this->assertEquals($task1->getId(), $todoTasks[0]->getId());
        $this->assertEquals($task2->getId(), $inProgressTasks[0]->getId());
    }

    public function testGetTasksByAssignee(): void
    {
        $this->taskService->createTask(new TaskDTO('Task 1', 'Description 1', 'user1'));
        $this->taskService->createTask(new TaskDTO('Task 2', 'Description 2', 'user2'));

        $user1Tasks = $this->taskService->getTasksByAssignee('user1');
        $user2Tasks = $this->taskService->getTasksByAssignee('user2');

        $this->assertCount(1, $user1Tasks);
        $this->assertCount(1, $user2Tasks);
        $this->assertEquals('user1', $user1Tasks[0]->getAssigneeId());
        $this->assertEquals('user2', $user2Tasks[0]->getAssigneeId());
    }

    public function testUpdateTaskStatus(): void
    {
        $task = $this->taskService->createTask(new TaskDTO('Test Task', 'Description'));
        $updatedTask = $this->taskService->updateTaskStatus($task->getId(), TaskStatus::IN_PROGRESS);

        $this->assertEquals(TaskStatus::IN_PROGRESS, $updatedTask->getStatus());
    }

    public function testUpdateNonExistentTaskStatus(): void
    {
        $this->expectException(TaskNotFoundException::class);
        $this->taskService->updateTaskStatus(Uuid::v4(), TaskStatus::IN_PROGRESS);
    }

    public function testAssignTask(): void
    {
        $task = $this->taskService->createTask(new TaskDTO('Test Task', 'Description'));
        $updatedTask = $this->taskService->assignTask($task->getId(), 'user123');

        $this->assertEquals('user123', $updatedTask->getAssigneeId());
    }

    public function testAssignNonExistentTask(): void
    {
        $this->expectException(TaskNotFoundException::class);
        $this->taskService->assignTask(Uuid::v4(), 'user123');
    }
} 