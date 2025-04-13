<?php

namespace App\Controller;

use App\DTO\TaskDTO;
use App\Enum\TaskStatus;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tasks', name: 'api_tasks_')]
final class TaskController extends AbstractController
{
    public function __construct(
        private TaskService $taskService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $taskDTO = $this->serializer->deserialize(
                $request->getContent(),
                TaskDTO::class,
                'json'
            );

            $violations = $this->validator->validate($taskDTO);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = [
                        'property' => $violation->getPropertyPath(),
                        'message' => $violation->getMessage(),
                    ];
                }
                return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $task = $this->taskService->createTask($taskDTO);
            return $this->json($task, Response::HTTP_CREATED, [], ['groups' => ['task']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $status = $request->query->get('status');
            $assigneeId = $request->query->get('assigneeId');

            if ($status) {
                $tasks = $this->taskService->getTasksByStatus(TaskStatus::from($status));
            } elseif ($assigneeId) {
                $tasks = $this->taskService->getTasksByAssignee($assigneeId);
            } else {
                $tasks = $this->taskService->getAllTasks();
            }

            return $this->json($tasks, Response::HTTP_OK, [], ['groups' => ['task']]);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid status value'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(Uuid $id): JsonResponse
    {
        $task = $this->taskService->getTask($id);
        if (!$task) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($task, Response::HTTP_OK, [], ['groups' => ['task']]);
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['PATCH'])]
    public function updateStatus(Uuid $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['status'])) {
                return $this->json(['error' => 'Status is required'], Response::HTTP_BAD_REQUEST);
            }

            $status = TaskStatus::from($data['status']);
            $task = $this->taskService->updateTaskStatus($id, $status);
            return $this->json($task, Response::HTTP_OK, [], ['groups' => ['task']]);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid status value'], Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/assign', name: 'assign', methods: ['PATCH'])]
    public function assign(Uuid $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['assigneeId'])) {
            return $this->json(['error' => 'Assignee ID is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $task = $this->taskService->assignTask($id, $data['assigneeId']);
            return $this->json($task, Response::HTTP_OK, [], ['groups' => ['task']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
} 