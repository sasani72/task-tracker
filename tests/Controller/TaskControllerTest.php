<?php

namespace App\Tests\Controller;

use App\Controller\TaskController;
use App\DTO\TaskDTO;
use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Repository\InMemoryTaskRepository;
use App\Service\TaskService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validation;

class TaskControllerTest extends TestCase
{
    private TaskController $controller;
    private TaskService $taskService;
    /** @var SerializerInterface&MockObject */
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $repository = new InMemoryTaskRepository();
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->taskService = new TaskService($repository, $validator);

        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->serializer->method('deserialize')
            ->willReturnCallback(function ($data, $type) {
                if ($type === TaskDTO::class) {
                    $data = json_decode($data, true);
                    return new TaskDTO(
                        $data['title'] ?? '',
                        $data['description'] ?? '',
                        $data['assigneeId'] ?? null
                    );
                }
                return null;
            });

        $this->serializer->method('serialize')
            ->willReturnCallback(function ($data, $format, $context = []) {
                if (is_array($data) && isset($data['error'])) {
                    return json_encode($data);
                }

                if ($data instanceof Task) {
                    return json_encode([
                        'id' => $data->getId()->toRfc4122(),
                        'title' => $data->getTitle(),
                        'description' => $data->getDescription(),
                        'status' => $data->getStatus()->value,
                        'assigneeId' => $data->getAssigneeId(),
                        'createdAt' => $data->getCreatedAt()->format('Y-m-d\TH:i:s\Z')
                    ]);
                } elseif (is_array($data)) {
                    return json_encode(array_map(function ($task) {
                        return [
                            'id' => $task->getId()->toRfc4122(),
                            'title' => $task->getTitle(),
                            'description' => $task->getDescription(),
                            'status' => $task->getStatus()->value,
                            'assigneeId' => $task->getAssigneeId(),
                            'createdAt' => $task->getCreatedAt()->format('Y-m-d\TH:i:s\Z')
                        ];
                    }, $data));
                }
                return json_encode($data);
            });

        $this->controller = new TaskController(
            $this->taskService,
            $this->serializer,
            $validator
        );

        // Set up container for AbstractController
        $container = new Container();
        $container->set('serializer', $this->serializer);
        $this->controller->setContainer($container);
    }

    public function testCreateTask(): void
    {
        $taskData = [
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'assigneeId' => 'user123'
        ];

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($taskData)
        );

        $response = $this->controller->create($request);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('title', $responseData);
        $this->assertArrayHasKey('description', $responseData);
        $this->assertArrayHasKey('assigneeId', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals($taskData['title'], $responseData['title']);
        $this->assertEquals($taskData['description'], $responseData['description']);
        $this->assertEquals($taskData['assigneeId'], $responseData['assigneeId']);
        $this->assertEquals(TaskStatus::TODO->value, $responseData['status']);
    }

    public function testCreateTaskWithInvalidData(): void
    {
        $taskData = [
            'title' => '', // Empty title should trigger validation error
            'description' => 'This is a test task',
            'assigneeId' => 'user123'
        ];

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($taskData)
        );

        $response = $this->controller->create($request);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testListTasks(): void
    {
        // Create some tasks first
        $task1 = $this->taskService->createTask(new TaskDTO('Task 1', 'Description 1'));
        $task2 = $this->taskService->createTask(new TaskDTO('Task 2', 'Description 2'));

        $request = new Request();
        $response = $this->controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData);
        $this->assertArrayHasKey('title', $responseData[0]);
        $this->assertArrayHasKey('title', $responseData[1]);
        $this->assertEquals('Task 1', $responseData[0]['title']);
        $this->assertEquals('Task 2', $responseData[1]['title']);
    }

    public function testListTasksByStatus(): void
    {
        $task1 = $this->taskService->createTask(new TaskDTO('Task 1', 'Description 1'));
        $task2 = $this->taskService->createTask(new TaskDTO('Task 2', 'Description 2'));
        $this->taskService->updateTaskStatus($task2->getId(), TaskStatus::IN_PROGRESS);

        $request = new Request(['status' => TaskStatus::IN_PROGRESS->value]);
        $response = $this->controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData);
        $this->assertArrayHasKey('status', $responseData[0]);
        $this->assertEquals(TaskStatus::IN_PROGRESS->value, $responseData[0]['status']);
        $this->assertEquals('Task 2', $responseData[0]['title']);
    }

    public function testListTasksByAssignee(): void
    {
        $this->taskService->createTask(new TaskDTO('Task 1', 'Description 1', 'user1'));
        $this->taskService->createTask(new TaskDTO('Task 2', 'Description 2', 'user2'));

        $request = new Request(['assigneeId' => 'user1']);
        $response = $this->controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData);
        $this->assertArrayHasKey('assigneeId', $responseData[0]);
        $this->assertEquals('user1', $responseData[0]['assigneeId']);
        $this->assertEquals('Task 1', $responseData[0]['title']);
    }

    public function testGetTask(): void
    {
        $task = $this->taskService->createTask(new TaskDTO('Test Task', 'Description'));

        $response = $this->controller->get($task->getId());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($task->getId()->toRfc4122(), $responseData['id']);
        $this->assertEquals('Test Task', $responseData['title']);
    }

    public function testGetNonExistentTask(): void
    {
        $response = $this->controller->get(Uuid::v4());
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testUpdateTaskStatus(): void
    {
        $task = $this->taskService->createTask(new TaskDTO('Test Task', 'Description'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => TaskStatus::IN_PROGRESS->value])
        );

        $response = $this->controller->updateStatus($task->getId(), $request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals(TaskStatus::IN_PROGRESS->value, $responseData['status']);
    }

    public function testUpdateTaskStatusWithInvalidData(): void
    {
        $task = $this->taskService->createTask(new TaskDTO('Test Task', 'Description'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'INVALID_STATUS'])
        );

        $response = $this->controller->updateStatus($task->getId(), $request);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testAssignTask(): void
    {
        $task = $this->taskService->createTask(new TaskDTO('Test Task', 'Description'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['assigneeId' => 'user123'])
        );

        $response = $this->controller->assign($task->getId(), $request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('assigneeId', $responseData);
        $this->assertEquals('user123', $responseData['assigneeId']);
    }

    public function testAssignTaskWithInvalidData(): void
    {
        $task = $this->taskService->createTask(new TaskDTO('Test Task', 'Description'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $response = $this->controller->assign($task->getId(), $request);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
} 